<?php

declare(strict_types=1);

require_once __DIR__ . '/PrototypeJudgeService.php';

final class ProblemPracticeService
{
    private PrototypeJudgeService $judge;

    public function __construct(private PDO $pdo)
    {
        $this->judge = new PrototypeJudgeService();
    }

    public function getProblem(string $problemId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*,
                    (SELECT COUNT(DISTINCT s.user_id) FROM submissions s WHERE s.problem_id = p.id AND s.verdict = \'AC\') AS solved_by
             FROM problems p WHERE p.id = :id'
        );
        $stmt->execute(['id' => $problemId]);
        return $stmt->fetch() ?: null;
    }

    public function hasSolved(string $userId, string $problemId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM submissions WHERE user_id = :uid AND problem_id = :pid AND verdict = \'AC\' LIMIT 1');
        $stmt->execute(['uid' => $userId, 'pid' => $problemId]);
        return (bool) $stmt->fetchColumn();
    }

    public function getOrCreateSession(string $userId, string $problemId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM problem_sessions
             WHERE user_id = :uid AND problem_id = :pid AND status = \'active\'
             ORDER BY started_at DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'pid' => $problemId]);
        $session = $stmt->fetch();
        if ($session) {
            return $session;
        }

        $id = generate_id('ps');
        $insert = $this->pdo->prepare(
            'INSERT INTO problem_sessions (id, user_id, problem_id, started_at, status)
             VALUES (:id, :uid, :pid, NOW(), \'active\')'
        );
        $insert->execute(['id' => $id, 'uid' => $userId, 'pid' => $problemId]);

        return [
            'id' => $id,
            'user_id' => $userId,
            'problem_id' => $problemId,
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'solve_time_seconds' => null,
            'status' => 'active',
        ];
    }

    public function submit(string $userId, string $sessionId, string $problemId, string $sourceCode, string $language): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ps.*, p.difficulty FROM problem_sessions ps
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.id = :sid AND ps.user_id = :uid AND ps.problem_id = :pid LIMIT 1'
        );
        $stmt->execute(['sid' => $sessionId, 'uid' => $userId, 'pid' => $problemId]);
        $session = $stmt->fetch();
        if (!$session || $session['status'] !== 'active') {
            throw new RuntimeException('This practice session is no longer active.');
        }

        $started = strtotime((string) $session['started_at']);
        $elapsed = max(1, time() - ($started !== false ? $started : time()));
        $judged = $this->judge->evaluate($sourceCode, $language, (string) $session['difficulty']);

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
                'sid' => $sessionId,
                'pid' => $problemId,
                'uid' => $userId,
                'verdict' => $judged['verdict'],
                'elapsed' => $elapsed,
                'runtime' => $judged['runtime_ms'],
                'memory' => $judged['memory_kb'],
                'language' => $language,
                'source' => $sourceCode,
                'failed' => $judged['failed_test_case'],
            ]);

            if ($judged['verdict'] === 'AC') {
                $finish = $this->pdo->prepare(
                    'UPDATE problem_sessions
                     SET status = \'solved\', completed_at = NOW(), solve_time_seconds = :elapsed
                     WHERE id = :id'
                );
                $finish->execute(['elapsed' => $elapsed, 'id' => $sessionId]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return array_merge($judged, ['id' => $submissionId, 'elapsed_seconds' => $elapsed]);
    }

    public function sessionAttempts(string $sessionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM submissions WHERE session_id = :sid ORDER BY elapsed_seconds ASC, submitted_at ASC');
        $stmt->execute(['sid' => $sessionId]);
        return $stmt->fetchAll();
    }
}
