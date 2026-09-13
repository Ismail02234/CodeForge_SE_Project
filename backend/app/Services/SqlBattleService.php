<?php

namespace App\Services;

use App\Support\Ids;
use PDO;
use RuntimeException;

final class SqlBattleService
{
    private SqlJudgeService $judge;

    public function __construct(private PDO $pdo)
    {
        $this->judge = new SqlJudgeService($pdo);
    }

    public function challenges(): array
    {
        return $this->pdo->query(
            "SELECT sc.*,
                    COALESCE(stats.accepted_attempts, 0) AS accepted_attempts,
                    COALESCE(stats.total_attempts, 0) AS total_attempts
             FROM sql_challenges sc
             LEFT JOIN (
                 SELECT challenge_id,
                        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_attempts,
                        COUNT(*) AS total_attempts
                 FROM sql_attempts
                 GROUP BY challenge_id
             ) stats ON stats.challenge_id = sc.id
             ORDER BY FIELD(sc.difficulty, 'Easy', 'Medium', 'Hard'), sc.id"
        )->fetchAll();
    }

    public function challenge(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sql_challenges WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function opponents(string $userId, ?string $challengeId = null): array
    {
        $challenge = $challengeId ?? '';

        $stmt = $this->pdo->prepare(
            "SELECT u.id,
                    u.username,
                    u.rating,
                    u.rank,
                    u.university,
                    COALESCE(stats.accepted_runs, 0) AS accepted_runs,
                    COALESCE(stats.best_score, 0) AS best_score,
                    active.id AS active_battle_id
             FROM users u
             LEFT JOIN (
                 SELECT user_id,
                        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_runs,
                        MAX(score) AS best_score
                 FROM sql_attempts
                 GROUP BY user_id
             ) stats ON stats.user_id = u.id
             LEFT JOIN sql_battles active
                    ON active.challenge_id = :challenge
                   AND active.status = 'active'
                   AND (
                        (active.player1_id = :me1 AND active.player2_id = u.id)
                        OR
                        (active.player2_id = :me2 AND active.player1_id = u.id)
                   )
             WHERE u.id <> :me3
             ORDER BY
                 CASE WHEN active.id IS NOT NULL THEN 0 ELSE 1 END,
                 u.rating DESC,
                 u.username ASC
             LIMIT 150"
        );

        $stmt->execute([
            'challenge' => $challenge,
            'me1' => $userId,
            'me2' => $userId,
            'me3' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function createBattle(string $challengeId, string $player1, string $player2): array
    {
        if ($player1 === $player2) {
            throw new RuntimeException('Choose another user as your SQL opponent.');
        }

        if (! $this->challenge($challengeId)) {
            throw new RuntimeException('SQL challenge not found.');
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM users WHERE id = :id');
        $exists->execute(['id' => $player2]);

        if (! $exists->fetchColumn()) {
            throw new RuntimeException('Opponent not found.');
        }

        $duplicate = $this->pdo->prepare(
            "SELECT id
             FROM sql_battles
             WHERE challenge_id = :challenge
               AND status = 'active'
               AND (
                    (player1_id = :p1 AND player2_id = :p2)
                    OR
                    (player1_id = :p2b AND player2_id = :p1b)
               )
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $duplicate->execute([
            'challenge' => $challengeId,
            'p1' => $player1,
            'p2' => $player2,
            'p2b' => $player2,
            'p1b' => $player1,
        ]);

        $existingId = $duplicate->fetchColumn();

        if ($existingId) {
            return [
                'id' => (string) $existingId,
                'created' => false,
                'message' => 'An active battle already exists. Resuming it.',
            ];
        }

        $id = Ids::make('sb');

        $stmt = $this->pdo->prepare(
            "INSERT INTO sql_battles
                (id, challenge_id, player1_id, player2_id, status)
             VALUES
                (:id, :challenge, :p1, :p2, 'active')"
        );

        $stmt->execute([
            'id' => $id,
            'challenge' => $challengeId,
            'p1' => $player1,
            'p2' => $player2,
        ]);

        return [
            'id' => $id,
            'created' => true,
            'message' => 'SQL Battle created.',
        ];
    }

    public function battle(string $battleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sb.*,
                    sc.title,
                    sc.description,
                    sc.difficulty,
                    sc.max_score,
                    sc.order_sensitive,
                    p1.username AS player1_name,
                    p1.rating AS player1_rating,
                    p2.username AS player2_name,
                    p2.rating AS player2_rating,
                    w.username AS winner_name
             FROM sql_battles sb
             INNER JOIN sql_challenges sc ON sc.id = sb.challenge_id
             INNER JOIN users p1 ON p1.id = sb.player1_id
             INNER JOIN users p2 ON p2.id = sb.player2_id
             LEFT JOIN users w ON w.id = sb.winner_id
             WHERE sb.id = :id'
        );

        $stmt->execute(['id' => $battleId]);

        return $stmt->fetch() ?: null;
    }

    public function submit(
        string $challengeId,
        string $userId,
        string $query,
        ?string $battleId = null
    ): array {
        $challenge = $this->challenge($challengeId);

        if (! $challenge) {
            throw new RuntimeException('SQL challenge not found.');
        }

        if ($battleId !== null) {
            $battle = $this->battle($battleId);

            if (! $battle) {
                throw new RuntimeException('SQL battle not found.');
            }

            if (
                ! in_array(
                    $userId,
                    [(string) $battle['player1_id'], (string) $battle['player2_id']],
                    true
                )
            ) {
                throw new RuntimeException('You are not a participant in this battle.');
            }

            if ($battle['status'] !== 'active') {
                throw new RuntimeException('This battle is already completed.');
            }

            if ((string) $battle['challenge_id'] !== $challengeId) {
                throw new RuntimeException('Battle/challenge mismatch.');
            }
        }

        $result = $this->judge->judge($challenge, $query);
        $attemptId = Ids::make('sa');

        $stmt = $this->pdo->prepare(
            'INSERT INTO sql_attempts
                (
                    id,
                    battle_id,
                    challenge_id,
                    user_id,
                    submitted_query,
                    status,
                    execution_time_ms,
                    efficiency_score,
                    score,
                    feedback
                )
             VALUES
                (
                    :id,
                    :battle,
                    :challenge,
                    :uid,
                    :query,
                    :status,
                    :time,
                    :efficiency,
                    :score,
                    :feedback
                )'
        );

        $stmt->execute([
            'id' => $attemptId,
            'battle' => $battleId,
            'challenge' => $challengeId,
            'uid' => $userId,
            'query' => $query,
            'status' => $result['status'],
            'time' => $result['execution_time_ms'],
            'efficiency' => $result['efficiency_score'],
            'score' => $result['score'],
            'feedback' => $result['feedback'],
        ]);

        if ($battleId !== null) {
            $this->maybeFinishBattle($battleId);
        }

        $result['attempt_id'] = $attemptId;

        return $result;
    }

    public function attemptsForBattle(string $battleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sa.*, u.username
             FROM sql_attempts sa
             INNER JOIN users u ON u.id = sa.user_id
             WHERE sa.battle_id = :id
             ORDER BY sa.submitted_at ASC, sa.id ASC'
        );

        $stmt->execute(['id' => $battleId]);

        return $stmt->fetchAll();
    }

    public function recentBattles(string $userId, int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));

        $stmt = $this->pdo->prepare(
            "SELECT sb.*,
                    sc.title,
                    sc.difficulty,
                    sc.max_score,
                    p1.username AS player1_name,
                    p2.username AS player2_name,
                    w.username AS winner_name,
                    COALESCE(a1.best_score, 0) AS player1_score,
                    COALESCE(a2.best_score, 0) AS player2_score,
                    COALESCE(a1.attempts, 0) AS player1_attempts,
                    COALESCE(a2.attempts, 0) AS player2_attempts
             FROM sql_battles sb
             INNER JOIN sql_challenges sc ON sc.id = sb.challenge_id
             INNER JOIN users p1 ON p1.id = sb.player1_id
             INNER JOIN users p2 ON p2.id = sb.player2_id
             LEFT JOIN users w ON w.id = sb.winner_id
             LEFT JOIN (
                 SELECT battle_id, user_id, MAX(score) AS best_score, COUNT(*) AS attempts
                 FROM sql_attempts
                 WHERE battle_id IS NOT NULL
                 GROUP BY battle_id, user_id
             ) a1 ON a1.battle_id = sb.id AND a1.user_id = sb.player1_id
             LEFT JOIN (
                 SELECT battle_id, user_id, MAX(score) AS best_score, COUNT(*) AS attempts
                 FROM sql_attempts
                 WHERE battle_id IS NOT NULL
                 GROUP BY battle_id, user_id
             ) a2 ON a2.battle_id = sb.id AND a2.user_id = sb.player2_id
             WHERE sb.player1_id = :uid1 OR sb.player2_id = :uid2
             ORDER BY
                 CASE WHEN sb.status = 'active' THEN 0 ELSE 1 END,
                 sb.created_at DESC
             LIMIT :limit"
        );

        $stmt->execute([
            'uid1' => $userId,
            'uid2' => $userId,
            'limit' => $limit,
        ]);

        return $stmt->fetchAll();
    }

    public function leaderboard(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        $stmt = $this->pdo->prepare(
            "SELECT u.id,
                    u.username,
                    u.rating,
                    u.rank,
                    MAX(sa.score) AS best_score,
                    SUM(CASE WHEN sa.status = 'accepted' THEN 1 ELSE 0 END) AS accepted_runs,
                    COUNT(*) AS total_runs
             FROM users u
             INNER JOIN sql_attempts sa ON sa.user_id = u.id
             GROUP BY u.id, u.username, u.rating, u.rank
             ORDER BY best_score DESC, accepted_runs DESC, u.rating DESC, u.username ASC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function maybeFinishBattle(string $battleId): void
    {
        $battle = $this->battle($battleId);

        if (! $battle || $battle['status'] !== 'active') {
            return;
        }

        $bestStmt = $this->pdo->prepare(
            "SELECT user_id, MAX(score) AS best_score
             FROM sql_attempts
             WHERE battle_id = :id
               AND status = 'accepted'
             GROUP BY user_id"
        );

        $bestStmt->execute(['id' => $battleId]);

        $best = [];

        foreach ($bestStmt->fetchAll() as $row) {
            $best[(string) $row['user_id']] = (int) $row['best_score'];
        }

        $player1 = (string) $battle['player1_id'];
        $player2 = (string) $battle['player2_id'];

        // A battle stays open until both players have at least one accepted result.
        if (! isset($best[$player1], $best[$player2])) {
            return;
        }

        $winner = null;

        if ($best[$player1] > $best[$player2]) {
            $winner = $player1;
        } elseif ($best[$player2] > $best[$player1]) {
            $winner = $player2;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE sql_battles
             SET status = 'completed',
                 winner_id = :winner,
                 completed_at = NOW()
             WHERE id = :id"
        );

        $stmt->execute([
            'winner' => $winner,
            'id' => $battleId,
        ]);

        if ($winner !== null) {
            $this->pdo->prepare(
                "INSERT INTO activity_logs (user_id, action, details)
                 VALUES (:uid, 'sql_battle.won', :details)"
            )->execute([
                'uid' => $winner,
                'details' => 'Won SQL Battle '.$battleId,
            ]);
        }
    }
}
