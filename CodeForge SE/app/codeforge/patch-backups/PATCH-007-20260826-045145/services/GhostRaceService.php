<?php

declare(strict_types=1);

require_once __DIR__ . '/PrototypeJudgeService.php';

final class GhostRaceService
{
    private PrototypeJudgeService $judge;

    public function __construct(private PDO $pdo)
    {
        $this->judge = new PrototypeJudgeService();
    }

    public function availableGhosts(string $challengerId): array
    {
        // Return the fastest representative run per user/problem instead of every
        // historical session. This prevents elite/demo accounts from flooding the
        // selector with hundreds of nearly identical ghost entries.
        $stmt = $this->pdo->prepare(
            "WITH ranked_sessions AS (
                SELECT ps.id, ps.user_id, ps.problem_id, ps.solve_time_seconds, ps.completed_at,
                       ROW_NUMBER() OVER (
                           PARTITION BY ps.user_id, ps.problem_id
                           ORDER BY ps.solve_time_seconds ASC, ps.completed_at DESC, ps.id ASC
                       ) AS rn
                FROM problem_sessions ps
                WHERE ps.status = 'solved'
                  AND ps.solve_time_seconds IS NOT NULL
                  AND ps.user_id <> :uid
             ), attempt_counts AS (
                SELECT session_id, COUNT(*) AS attempts
                FROM submissions
                WHERE session_id IS NOT NULL
                GROUP BY session_id
             )
             SELECT rs.id AS session_id,
                    rs.user_id AS ghost_user_id,
                    u.username AS ghost_username,
                    rs.problem_id,
                    p.title AS problem_title,
                    p.topic,
                    p.difficulty,
                    rs.solve_time_seconds,
                    COALESCE(ac.attempts, 0) AS attempts
             FROM ranked_sessions rs
             INNER JOIN users u ON u.id = rs.user_id
             INNER JOIN problems p ON p.id = rs.problem_id
             LEFT JOIN attempt_counts ac ON ac.session_id = rs.id
             WHERE rs.rn = 1
             ORDER BY p.title ASC, rs.solve_time_seconds ASC, u.username ASC
             LIMIT 250"
        );
        $stmt->execute(['uid' => $challengerId]);
        return $stmt->fetchAll();
    }

    public function createRace(string $challengerId, string $ghostSessionId, int $playbackSpeed = 4): string
    {
        $speed = in_array($playbackSpeed, [1, 2, 4], true) ? $playbackSpeed : 4;

        $active = $this->pdo->prepare(
            "SELECT id FROM ghost_races
             WHERE challenger_id = :uid AND result = 'active'
             ORDER BY started_at DESC LIMIT 1"
        );
        $active->execute(['uid' => $challengerId]);
        if ($active->fetchColumn()) {
            throw new RuntimeException('Finish or forfeit your current Ghost Race before starting another one.');
        }

        $stmt = $this->pdo->prepare(
            "SELECT ps.id, ps.user_id, ps.problem_id, ps.solve_time_seconds, p.title, p.difficulty
             FROM problem_sessions ps
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.id = :sid
               AND ps.status = 'solved'
               AND ps.solve_time_seconds IS NOT NULL
             LIMIT 1"
        );
        $stmt->execute(['sid' => $ghostSessionId]);
        $ghost = $stmt->fetch();

        if (!$ghost) {
            throw new RuntimeException('Selected ghost run is unavailable.');
        }
        if ((string) $ghost['user_id'] === $challengerId) {
            throw new RuntimeException('Choose another user as your ghost opponent.');
        }

        $raceId = generate_id('gr');
        $sessionId = generate_id('ps');

        $this->pdo->beginTransaction();
        try {
            $insertSession = $this->pdo->prepare(
                "INSERT INTO problem_sessions (id, user_id, problem_id, started_at, status)
                 VALUES (:id, :uid, :pid, NOW(), 'active')"
            );
            $insertSession->execute([
                'id' => $sessionId,
                'uid' => $challengerId,
                'pid' => $ghost['problem_id'],
            ]);

            $insertRace = $this->pdo->prepare(
                "INSERT INTO ghost_races
                    (id, challenger_id, ghost_user_id, problem_id, ghost_session_id,
                     challenger_session_id, playback_speed, started_at, result, ghost_time)
                 VALUES
                    (:id, :challenger, :ghost_user, :problem, :ghost_session,
                     :challenger_session, :speed, NOW(), 'active', :ghost_time)"
            );
            $insertRace->execute([
                'id' => $raceId,
                'challenger' => $challengerId,
                'ghost_user' => $ghost['user_id'],
                'problem' => $ghost['problem_id'],
                'ghost_session' => $ghostSessionId,
                'challenger_session' => $sessionId,
                'speed' => $speed,
                'ghost_time' => $ghost['solve_time_seconds'],
            ]);

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return $raceId;
    }

    public function getRace(string $raceId, string $challengerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT gr.*, p.title AS problem_title, p.topic, p.difficulty, p.description, p.starter_code,
                    ghost.username AS ghost_username,
                    challenger.username AS challenger_username,
                    cps.started_at AS challenger_started_at,
                    cps.status AS challenger_session_status
             FROM ghost_races gr
             INNER JOIN problems p ON p.id = gr.problem_id
             INNER JOIN users ghost ON ghost.id = gr.ghost_user_id
             INNER JOIN users challenger ON challenger.id = gr.challenger_id
             INNER JOIN problem_sessions cps ON cps.id = gr.challenger_session_id
             WHERE gr.id = :id AND gr.challenger_id = :uid LIMIT 1'
        );
        $stmt->execute(['id' => $raceId, 'uid' => $challengerId]);
        return $stmt->fetch() ?: null;
    }

    public function ghostEvents(string $ghostSessionId): array
    {
        return $this->eventsForSession($ghostSessionId);
    }

    public function challengerEvents(string $challengerSessionId): array
    {
        return $this->eventsForSession($challengerSessionId);
    }

    public function virtualElapsed(array $race): int
    {
        $started = strtotime((string) $race['started_at']);
        if ($started === false) {
            return 0;
        }

        $realSeconds = max(0, time() - $started);
        return $realSeconds * max(1, (int) $race['playback_speed']);
    }

    public function submit(string $raceId, string $challengerId, string $sourceCode, string $language): array
    {
        $race = $this->getRace($raceId, $challengerId);
        if (!$race) {
            throw new RuntimeException('Race not found.');
        }
        if ($race['result'] !== 'active') {
            throw new RuntimeException('This race is already finished.');
        }

        $elapsed = max(1, $this->virtualElapsed($race));
        $judged = $this->judge->evaluate($sourceCode, $language, (string) $race['difficulty']);
        $submissionId = generate_id('sub');

        $this->pdo->beginTransaction();
        try {
            // Guard against double-submit races by re-checking the state under a row lock.
            $lock = $this->pdo->prepare(
                "SELECT result FROM ghost_races
                 WHERE id = :id AND challenger_id = :uid
                 FOR UPDATE"
            );
            $lock->execute(['id' => $raceId, 'uid' => $challengerId]);
            $lockedState = $lock->fetchColumn();
            if ($lockedState !== 'active') {
                throw new RuntimeException('This race was already finished in another request.');
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO submissions
                    (id, session_id, problem_id, user_id, verdict, submitted_at, elapsed_seconds,
                     runtime_ms, memory_kb, language, source_code, failed_test_case)
                 VALUES
                    (:id, :sid, :pid, :uid, :verdict, NOW(), :elapsed,
                     :runtime, :memory, :language, :source, :failed)'
            );
            $insert->execute([
                'id' => $submissionId,
                'sid' => $race['challenger_session_id'],
                'pid' => $race['problem_id'],
                'uid' => $challengerId,
                'verdict' => $judged['verdict'],
                'elapsed' => $elapsed,
                'runtime' => $judged['runtime_ms'],
                'memory' => $judged['memory_kb'],
                'language' => $language,
                'source' => $sourceCode,
                'failed' => $judged['failed_test_case'],
            ]);

            if ($judged['verdict'] === 'AC') {
                $ghostTime = (int) $race['ghost_time'];
                $result = $elapsed < $ghostTime ? 'won' : ($elapsed === $ghostTime ? 'draw' : 'lost');

                $this->pdo->prepare(
                    "UPDATE problem_sessions
                     SET status = 'solved', completed_at = NOW(), solve_time_seconds = :elapsed
                     WHERE id = :id AND status = 'active'"
                )->execute([
                    'elapsed' => $elapsed,
                    'id' => $race['challenger_session_id'],
                ]);

                $this->pdo->prepare(
                    'UPDATE ghost_races
                     SET result = :result, challenger_time = :elapsed, finished_at = NOW()
                     WHERE id = :id AND result = \'active\''
                )->execute([
                    'result' => $result,
                    'elapsed' => $elapsed,
                    'id' => $raceId,
                ]);

                $this->pdo->prepare(
                    'INSERT INTO activity_logs (user_id, action, details)
                     VALUES (:uid, :action, :details)'
                )->execute([
                    'uid' => $challengerId,
                    'action' => 'ghost_race.completed',
                    'details' => sprintf(
                        '%s ghost race against %s on %s',
                        ucfirst($result),
                        $race['ghost_username'],
                        $race['problem_title']
                    ),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return array_merge($judged, [
            'elapsed_seconds' => $elapsed,
            'submission_id' => $submissionId,
        ]);
    }

    public function forfeit(string $raceId, string $challengerId): void
    {
        $race = $this->getRace($raceId, $challengerId);
        if (!$race || $race['result'] !== 'active') {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $updated = $this->pdo->prepare(
                "UPDATE ghost_races
                 SET result = 'forfeit', finished_at = NOW()
                 WHERE id = :id AND challenger_id = :uid AND result = 'active'"
            );
            $updated->execute(['id' => $raceId, 'uid' => $challengerId]);

            if ($updated->rowCount() > 0) {
                $this->pdo->prepare(
                    "UPDATE problem_sessions
                     SET status = 'abandoned', completed_at = NOW()
                     WHERE id = :id AND status = 'active'"
                )->execute(['id' => $race['challenger_session_id']]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function history(string $challengerId, int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT gr.*, p.title AS problem_title, u.username AS ghost_username
             FROM ghost_races gr
             INNER JOIN problems p ON p.id = gr.problem_id
             INNER JOIN users u ON u.id = gr.ghost_user_id
             WHERE gr.challenger_id = :uid
             ORDER BY gr.started_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['uid' => $challengerId]);
        return $stmt->fetchAll();
    }

    private function eventsForSession(string $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, verdict, elapsed_seconds, runtime_ms, language
             FROM submissions
             WHERE session_id = :sid
             ORDER BY elapsed_seconds ASC, submitted_at ASC'
        );
        $stmt->execute(['sid' => $sessionId]);
        return $stmt->fetchAll();
    }
}
