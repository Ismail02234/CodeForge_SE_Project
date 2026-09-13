<?php

namespace App\Services;

use App\Support\Ids;
use PDO;
use RuntimeException;
use Throwable;

final class ProblemPracticeService
{
    private PrototypeJudgeService $judge;

    public function __construct(private PDO $pdo)
    {
        $this->judge = new PrototypeJudgeService;
    }

    public function getProblem(string $problemId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, COALESCE(stats.solved_by, 0) AS solved_by
             FROM problems p
             LEFT JOIN (
                 SELECT problem_id, COUNT(DISTINCT user_id) AS solved_by
                 FROM submissions
                 WHERE problem_id = :stats_id AND verdict = 'AC'
                 GROUP BY problem_id
             ) stats ON stats.problem_id = p.id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute(['stats_id' => $problemId, 'id' => $problemId]);

        return $stmt->fetch() ?: null;
    }

    public function hasSolved(string $userId, string $problemId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM submissions
             WHERE user_id = :uid AND problem_id = :pid AND verdict = 'AC'
             LIMIT 1"
        );
        $stmt->execute(['uid' => $userId, 'pid' => $problemId]);

        return (bool) $stmt->fetchColumn();
    }

    public function getOrCreateSession(string $userId, string $problemId): array
    {
        // ghost race sessions are handled separately
        $stmt = $this->pdo->prepare(
            "SELECT ps.*
             FROM problem_sessions ps
             WHERE ps.user_id = :uid
               AND ps.problem_id = :pid
               AND ps.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM ghost_races gr
                   WHERE gr.challenger_session_id = ps.id AND gr.result = 'active'
               )
             ORDER BY ps.started_at DESC
             LIMIT 1"
        );
        $stmt->execute(['uid' => $userId, 'pid' => $problemId]);
        $session = $stmt->fetch();
        if ($session) {
            return $session;
        }

        $id = Ids::make('ps');
        $insert = $this->pdo->prepare(
            "INSERT INTO problem_sessions (id, user_id, problem_id, started_at, status)
             VALUES (:id, :uid, :pid, NOW(), 'active')"
        );
        $insert->execute(['id' => $id, 'uid' => $userId, 'pid' => $problemId]);

        $fetch = $this->pdo->prepare('SELECT * FROM problem_sessions WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return $fetch->fetch() ?: [
            'id' => $id,
            'user_id' => $userId,
            'problem_id' => $problemId,
            'started_at' => gmdate('Y-m-d H:i:s'),
            'completed_at' => null,
            'solve_time_seconds' => null,
            'status' => 'active',
        ];
    }

    public function submit(
        string $userId,
        string $sessionId,
        string $problemId,
        string $sourceCode,
        string $language,
        ?string $contestId = null
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT ps.id, ps.user_id, ps.problem_id, ps.started_at, ps.status, p.difficulty
             FROM problem_sessions ps
             INNER JOIN problems p ON p.id = ps.problem_id
             WHERE ps.id = :sid AND ps.user_id = :uid AND ps.problem_id = :pid
             LIMIT 1'
        );
        $stmt->execute(['sid' => $sessionId, 'uid' => $userId, 'pid' => $problemId]);
        $session = $stmt->fetch();

        if (! $session || $session['status'] !== 'active') {
            throw new RuntimeException('This practice session is no longer active.');
        }

        $ghostCheck = $this->pdo->prepare(
            "SELECT 1 FROM ghost_races
             WHERE challenger_session_id = :sid AND result = 'active'
             LIMIT 1"
        );
        $ghostCheck->execute(['sid' => $sessionId]);
        if ($ghostCheck->fetchColumn()) {
            throw new RuntimeException('This session belongs to an active Ghost Race. Submit from the race screen instead.');
        }

        $contestPoints = 0;
        $alreadyAcceptedInContest = false;

        if ($contestId !== null && $contestId !== '') {
            $contest = $this->pdo->prepare(
                'SELECT c.status, cp.points,
                        EXISTS(
                            SELECT 1 FROM contest_participants cpa
                            WHERE cpa.contest_id = c.id AND cpa.user_id = :uid
                        ) AS joined
                 FROM contests c
                 INNER JOIN contest_problems cp
                    ON cp.contest_id = c.id AND cp.problem_id = :pid
                 WHERE c.id = :cid
                 LIMIT 1'
            );
            $contest->execute(['uid' => $userId, 'pid' => $problemId, 'cid' => $contestId]);
            $contestRow = $contest->fetch();

            if (! $contestRow) {
                throw new RuntimeException('This problem is not part of the selected contest.');
            }
            if (! (bool) $contestRow['joined']) {
                throw new RuntimeException('Join the contest before submitting contest solutions.');
            }
            if ((string) $contestRow['status'] !== 'Active') {
                throw new RuntimeException(
                    (string) $contestRow['status'] === 'Past'
                        ? 'This contest is already closed.'
                        : 'This contest is not active yet.'
                );
            }

            $contestPoints = (int) $contestRow['points'];
            $prior = $this->pdo->prepare(
                "SELECT 1 FROM submissions
                 WHERE user_id = :uid
                   AND problem_id = :pid
                   AND contest_id = :cid
                   AND verdict = 'AC'
                 LIMIT 1"
            );
            $prior->execute(['uid' => $userId, 'pid' => $problemId, 'cid' => $contestId]);
            $alreadyAcceptedInContest = (bool) $prior->fetchColumn();
        }

        $started = strtotime((string) $session['started_at']);
        $elapsed = max(1, time() - ($started !== false ? $started : time()));
        $judged = $this->judge->evaluate($sourceCode, $language, (string) $session['difficulty']);
        $submissionId = Ids::make('sub');

        $this->pdo->beginTransaction();
        try {
            $sessionLock = $this->pdo->prepare(
                'SELECT status FROM problem_sessions WHERE id = :id FOR UPDATE'
            );
            $sessionLock->execute(['id' => $sessionId]);
            if ($sessionLock->fetchColumn() !== 'active') {
                throw new RuntimeException('This practice session was completed in another request.');
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO submissions
                    (id, session_id, problem_id, user_id, contest_id, verdict, submitted_at,
                     elapsed_seconds, runtime_ms, memory_kb, language, source_code, failed_test_case)
                 VALUES
                    (:id, :sid, :pid, :uid, :contest, :verdict, NOW(),
                     :elapsed, :runtime, :memory, :language, :source, :failed)'
            );
            $insert->execute([
                'id' => $submissionId,
                'sid' => $sessionId,
                'pid' => $problemId,
                'uid' => $userId,
                'contest' => $contestId ?: null,
                'verdict' => $judged['verdict'],
                'elapsed' => $elapsed,
                'runtime' => $judged['runtime_ms'],
                'memory' => $judged['memory_kb'],
                'language' => $language,
                'source' => $sourceCode,
                'failed' => $judged['failed_test_case'],
            ]);

            if ($judged['verdict'] === 'AC') {
                $this->pdo->prepare(
                    "UPDATE problem_sessions
                     SET status = 'solved', completed_at = NOW(), solve_time_seconds = :elapsed
                     WHERE id = :id AND status = 'active'"
                )->execute(['elapsed' => $elapsed, 'id' => $sessionId]);

                if ($contestId && ! $alreadyAcceptedInContest && $contestPoints > 0) {
                    $this->pdo->prepare(
                        'UPDATE contest_participants
                         SET score = score + :points
                         WHERE contest_id = :cid AND user_id = :uid'
                    )->execute([
                        'points' => $contestPoints,
                        'cid' => $contestId,
                        'uid' => $userId,
                    ]);
                }

                $this->notifyLearningProgress($userId, $problemId);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return array_merge($judged, [
            'id' => $submissionId,
            'elapsed_seconds' => $elapsed,
        ]);
    }

    public function sessionAttempts(string $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, verdict, submitted_at, elapsed_seconds, runtime_ms, memory_kb,
                    language, failed_test_case
             FROM submissions
             WHERE session_id = :sid
             ORDER BY elapsed_seconds ASC, submitted_at ASC'
        );
        $stmt->execute(['sid' => $sessionId]);

        return $stmt->fetchAll();
    }

    private function notifyLearningProgress(string $userId, string $problemId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT lp.learning_module_id
             FROM learning_problems lp
             INNER JOIN problems p ON p.id = lp.problem_id
             WHERE lp.problem_id = :pid'
        );
        $stmt->execute(['pid' => $problemId]);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (! $links) {
            return;
        }

        $moduleIds = array_unique(array_column($links, 'learning_module_id'));

        foreach ($moduleIds as $moduleId) {
            $this->updateModuleProveProgress($userId, $moduleId);
        }
    }

    private function updateModuleProveProgress(string $userId, string $moduleId): void
    {
        $problemsStmt = $this->pdo->prepare(
            'SELECT lp.problem_id
             FROM learning_problems lp
             INNER JOIN problems p ON p.id = lp.problem_id
             WHERE lp.learning_module_id = :mid'
        );
        $problemsStmt->execute(['mid' => $moduleId]);
        $allProblems = $problemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (! $allProblems) {
            return;
        }

        $solvedScore = 0;
        $allSolved = true;

        foreach ($allProblems as $p) {
            if ($this->hasSolved($userId, $p['problem_id'])) {
                $solvedScore++;
            } else {
                $allSolved = false;
            }
        }

        $progressStmt = $this->pdo->prepare(
            'SELECT * FROM learning_progress
             WHERE user_id = :uid AND learning_module_id = :mid
             LIMIT 1'
        );
        $progressStmt->execute(['uid' => $userId, 'mid' => $moduleId]);
        $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);

        $now = gmdate('Y-m-d H:i:s');

        if (! $progress) {
            $progressId = 'lp_'.bin2hex(random_bytes(8));
            $this->pdo->prepare(
                'INSERT INTO learning_progress
                    (id, user_id, learning_module_id, learn_completed, play_completed, prove_completed,
                     learn_score, play_score, prove_score, mastery_score, attempts, hints_used,
                     started_at, completed_at, created_at)
                 VALUES
                    (:id, :uid, :mid, 0, 0, :prove_completed, 0, 0, :prove_score, :mastery_score,
                     1, 0, :now, :completed_at, :now)'
            )->execute([
                'id' => $progressId,
                'uid' => $userId,
                'mid' => $moduleId,
                'prove_completed' => $allSolved ? 1 : 0,
                'prove_score' => $solvedScore,
                'mastery_score' => $solvedScore,
                'now' => $now,
                'completed_at' => $allSolved ? $now : null,
            ]);

            return;
        }

        $proveScore = max((int) ($progress['prove_score'] ?? 0), $solvedScore);
        $masteryScore = (int) ($progress['learn_score'] ?? 0) + (int) ($progress['play_score'] ?? 0) + $proveScore;

        $update = [
            'prove_score' => $proveScore,
            'mastery_score' => $masteryScore,
        ];

        if ($allSolved) {
            $update['prove_completed'] = true;
        }

        $learnCompleted = (bool) ($progress['learn_completed'] ?? false);
        $playCompleted = (bool) ($progress['play_completed'] ?? false);

        if ($allSolved && $learnCompleted && $playCompleted) {
            $update['completed_at'] = $now;
        }

        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($update)));
        $params = array_merge(['id' => $progress['id']], $update);

        $this->pdo->prepare("UPDATE learning_progress SET $set WHERE id = :id")->execute($params);
    }
}
