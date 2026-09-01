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
            "SELECT sc.*, COALESCE(stats.accepted_attempts, 0) AS accepted_attempts
             FROM sql_challenges sc
             LEFT JOIN (
                 SELECT challenge_id, COUNT(*) AS accepted_attempts
                 FROM sql_attempts
                 WHERE status = 'accepted'
                 GROUP BY challenge_id
             ) stats ON stats.challenge_id = sc.id
             ORDER BY FIELD(sc.difficulty,'Easy','Medium','Hard'), sc.id"
        )->fetchAll();
    }

    public function challenge(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sql_challenges WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function createBattle(string $challengeId, string $player1, string $player2): string
    {
        if ($player1 === $player2) {
            throw new RuntimeException('Choose another user as your SQL opponent.');
        }
        if (! $this->challenge($challengeId)) {
            throw new RuntimeException('SQL challenge not found.');
        }

        $duplicate = $this->pdo->prepare(
            "SELECT id FROM sql_battles
             WHERE challenge_id = :challenge
               AND status = 'active'
               AND ((player1_id = :p1 AND player2_id = :p2)
                 OR (player1_id = :p2b AND player2_id = :p1b))
             LIMIT 1"
        );
        $duplicate->execute([
            'challenge' => $challengeId,
            'p1' => $player1,
            'p2' => $player2,
            'p2b' => $player2,
            'p1b' => $player1,
        ]);
        if ($duplicate->fetchColumn()) {
            throw new RuntimeException('An active battle with this opponent already exists for the selected challenge.');
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM users WHERE id = :id');
        $exists->execute(['id' => $player2]);
        if (! $exists->fetchColumn()) {
            throw new RuntimeException('Opponent not found.');
        }

        $id = Ids::make('sb');
        $stmt = $this->pdo->prepare(
            'INSERT INTO sql_battles (id, challenge_id, player1_id, player2_id, status)
             VALUES (:id, :challenge, :p1, :p2, \'active\')'
        );
        $stmt->execute(['id' => $id, 'challenge' => $challengeId, 'p1' => $player1, 'p2' => $player2]);

        return $id;
    }

    public function battle(string $battleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sb.*, sc.title, sc.description, sc.difficulty, sc.max_score,
                    p1.username AS player1_name, p2.username AS player2_name,
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

    public function submit(string $challengeId, string $userId, string $query, ?string $battleId = null): array
    {
        $challenge = $this->challenge($challengeId);
        if (! $challenge) {
            throw new RuntimeException('SQL challenge not found.');
        }

        if ($battleId !== null) {
            $battle = $this->battle($battleId);
            if (! $battle) {
                throw new RuntimeException('SQL battle not found.');
            }
            if (! in_array($userId, [(string) $battle['player1_id'], (string) $battle['player2_id']], true)) {
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
                (id, battle_id, challenge_id, user_id, submitted_query, status, execution_time_ms, efficiency_score, score, feedback)
             VALUES
                (:id, :battle, :challenge, :uid, :query, :status, :time, :efficiency, :score, :feedback)'
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
             WHERE sa.battle_id = :id ORDER BY sa.submitted_at ASC'
        );
        $stmt->execute(['id' => $battleId]);

        return $stmt->fetchAll();
    }

    public function recentBattles(string $userId, int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT sb.*, sc.title, p1.username AS player1_name, p2.username AS player2_name, w.username AS winner_name
             FROM sql_battles sb
             INNER JOIN sql_challenges sc ON sc.id = sb.challenge_id
             INNER JOIN users p1 ON p1.id = sb.player1_id
             INNER JOIN users p2 ON p2.id = sb.player2_id
             LEFT JOIN users w ON w.id = sb.winner_id
             WHERE sb.player1_id = :uid OR sb.player2_id = :uid
             ORDER BY sb.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['uid' => $userId]);

        return $stmt->fetchAll();
    }

    public function leaderboard(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        return $this->pdo->query(
            "SELECT u.id, u.username, MAX(sa.score) AS best_score, COUNT(CASE WHEN sa.status = 'accepted' THEN 1 END) AS accepted_runs
             FROM users u
             INNER JOIN sql_attempts sa ON sa.user_id = u.id
             GROUP BY u.id, u.username
             ORDER BY best_score DESC, accepted_runs DESC, u.username ASC
             LIMIT {$limit}"
        )->fetchAll();
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
             WHERE battle_id = :id AND status = 'accepted'
             GROUP BY user_id"
        );
        $bestStmt->execute(['id' => $battleId]);
        $best = [];
        foreach ($bestStmt->fetchAll() as $row) {
            $best[(string) $row['user_id']] = (int) $row['best_score'];
        }

        $p1 = (string) $battle['player1_id'];
        $p2 = (string) $battle['player2_id'];
        if (! isset($best[$p1], $best[$p2])) {
            return;
        }

        $winner = null;
        if ($best[$p1] > $best[$p2]) {
            $winner = $p1;
        } elseif ($best[$p2] > $best[$p1]) {
            $winner = $p2;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE sql_battles SET status = \'completed\', winner_id = :winner, completed_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['winner' => $winner, 'id' => $battleId]);

        if ($winner !== null) {
            $this->pdo->prepare('INSERT INTO activity_logs (user_id, action, details) VALUES (:uid, \'sql_battle.won\', :details)')
                ->execute(['uid' => $winner, 'details' => 'Won SQL Battle '.$battleId]);
        }
    }
}
