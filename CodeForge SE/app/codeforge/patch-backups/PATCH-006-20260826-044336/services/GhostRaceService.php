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
        $stmt = $this->pdo->prepare(
            'SELECT ps.id AS session_id, ps.user_id AS ghost_user_id, u.username AS ghost_username,
                    ps.problem_id, p.title AS problem_title, p.topic, p.difficulty,
                    ps.solve_time_seconds,
                    (SELECT COUNT(*) FROM submissions s WHERE s.session_id = ps.id) AS attempts
             FROM problem_sessions ps
             INNER JOIN users u ON u.id = ps.user_id
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.status = \'solved\'
               AND ps.solve_time_seconds IS NOT NULL
               AND ps.user_id <> :uid
             ORDER BY p.title ASC, ps.solve_time_seconds ASC'
        );
        $stmt->execute(['uid' => $challengerId]);
        return $stmt->fetchAll();
    }

    public function createRace(string $challengerId, string $ghostSessionId, int $playbackSpeed = 4): string
    {
        $speed = in_array($playbackSpeed, [1, 2, 4], true) ? $playbackSpeed : 4;

        $stmt = $this->pdo->prepare(
            'SELECT ps.*, p.title, p.difficulty
             FROM problem_sessions ps
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.id = :sid AND ps.status = \'solved\' AND ps.solve_time_seconds IS NOT NULL LIMIT 1'
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
                'INSERT INTO problem_sessions (id, user_id, problem_id, started_at, status)
                 VALUES (:id, :uid, :pid, NOW(), \'active\')'
            );
            $insertSession->execute([
                'id' => $sessionId,
                'uid' => $challengerId,
                'pid' => $ghost['problem_id'],
            ]);

            $insertRace = $this->pdo->prepare(
                'INSERT INTO ghost_races
                    (id, challenger_id, ghost_user_id, problem_id, ghost_session_id, challenger_session_id, playback_speed, started_at, result, ghost_time)
                 VALUES
                    (:id, :challenger, :ghost_user, :problem, :ghost_session, :challenger_session, :speed, NOW(), \'active\', :ghost_time)'
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
        $stmt = $this->pdo->prepare(
            'SELECT id, verdict, elapsed_seconds, runtime_ms, language
             FROM submissions WHERE session_id = :sid
             ORDER BY elapsed_seconds ASC, submitted_at ASC'
        );
        $stmt->execute(['sid' => $ghostSessionId]);
        return $stmt->fetchAll();
    }

    public function challengerEvents(string $challengerSessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, verdict, elapsed_seconds, runtime_ms, language
             FROM submissions WHERE session_id = :sid
             ORDER BY elapsed_seconds ASC, submitted_at ASC'
        );
        $stmt->execute(['sid' => $challengerSessionId]);
        return $stmt->fetchAll();
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
            $insert = $this->pdo->prepare(
                'INSERT INTO submissions
                    (id, session_id, problem_id, user_id, verdict, submitted_at, elapsed_seconds, runtime_ms, memory_kb, language, source_code, failed_test_case)
                 VALUES
                    (:id, :sid, :pid, :uid, :verdict, NOW(), :elapsed, :runtime, :memory, :language, :source, :failed)'
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

                $finishSession = $this->pdo->prepare(
                    'UPDATE problem_sessions
                     SET status = \'solved\', completed_at = NOW(), solve_time_seconds = :elapsed
                     WHERE id = :id'
                );
                $finishSession->execute(['elapsed' => $elapsed, 'id' => $race['challenger_session_id']]);

                $finishRace = $this->pdo->prepare(
                    'UPDATE ghost_races
                     SET result = :result, challenger_time = :elapsed, finished_at = NOW()
                     WHERE id = :id'
                );
                $finishRace->execute(['result' => $result, 'elapsed' => $elapsed, 'id' => $raceId]);

                $log = $this->pdo->prepare('INSERT INTO activity_logs (user_id, action, details) VALUES (:uid, :action, :details)');
                $log->execute([
                    'uid' => $challengerId,
                    'action' => 'ghost_race.completed',
                    'details' => sprintf('%s ghost race against %s on %s', ucfirst($result), $race['ghost_username'], $race['problem_title']),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return array_merge($judged, ['elapsed_seconds' => $elapsed, 'submission_id' => $submissionId]);
    }

    public function forfeit(string $raceId, string $challengerId): void
    {
        $race = $this->getRace($raceId, $challengerId);
        if (!$race || $race['result'] !== 'active') {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE ghost_races SET result = \'forfeit\', finished_at = NOW() WHERE id = :id')
                ->execute(['id' => $raceId]);
            $this->pdo->prepare('UPDATE problem_sessions SET status = \'abandoned\', completed_at = NOW() WHERE id = :id')
                ->execute(['id' => $race['challenger_session_id']]);
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
             ORDER BY gr.started_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['uid' => $challengerId]);
        return $stmt->fetchAll();
    }
}
