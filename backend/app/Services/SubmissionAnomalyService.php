<?php

namespace App\Services;

use PDO;

final class SubmissionAnomalyService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function analyze(string $userId): array
    {
        $submissions = $this->getSubmissions($userId);

        $rapidAttempts = 0;
        $repeatedFailures = 0;
        $similarityFlags = 0;

        $byProblem = [];

        foreach ($submissions as $submission) {
            $problemId = (string) $submission['problem_id'];

            $byProblem[$problemId][] = $submission;
        }

        /*
         * 1. Rapid submission detection
         * More than 3 submissions within 60 seconds
         */
        foreach ($submissions as $index => $submission) {
            if (! isset($submissions[$index - 1])) {
                continue;
            }

            $current = strtotime((string) $submission['submitted_at']);
            $previous = strtotime((string) $submissions[$index - 1]['submitted_at']);

            if ($current !== false && $previous !== false) {
                $gap = $current - $previous;

                if ($gap >= 0 && $gap <= 60) {
                    $rapidAttempts++;
                }
            }
        }

        /*
         * 2. Repeated failure detection
         * 3 or more failed attempts on the same problem
         */
        foreach ($byProblem as $attempts) {
            $failed = 0;

            foreach ($attempts as $attempt) {
                if ((string) $attempt['verdict'] !== 'AC') {
                    $failed++;
                }
            }

            if ($failed >= 3) {
                $repeatedFailures++;
            }
        }

        /*
         * 3. Code similarity detection
         * Compare the current code with the user's previous code.
         */
        foreach ($submissions as $index => $submission) {
            if ($index === 0) {
                continue;
            }

            $currentCode = trim((string) ($submission['source_code'] ?? ''));

            if ($currentCode === '') {
                continue;
            }

            foreach (array_slice($submissions, 0, $index) as $previous) {
                $previousCode = trim((string) ($previous['source_code'] ?? ''));

                if ($previousCode === '') {
                    continue;
                }

                $similarity = $this->similarity($currentCode, $previousCode);

                if ($similarity >= 0.92) {
                    $similarityFlags++;
                    break;
                }
            }
        }

        $totalFlags = $rapidAttempts + $repeatedFailures + $similarityFlags;

        $riskLevel = match (true) {
            $totalFlags >= 8 => 'HIGH',
            $totalFlags >= 4 => 'MEDIUM',
            $totalFlags >= 1 => 'LOW',
            default => 'NORMAL',
        };

        return [
            'summary' => [
                'normal' => max(0, count($submissions) - $totalFlags),
                'rapid_attempts' => $rapidAttempts,
                'repeated_failures' => $repeatedFailures,
                'similarity_flags' => $similarityFlags,
                'total_flags' => $totalFlags,
                'risk_level' => $riskLevel,
            ],
            'message' => $this->riskMessage($riskLevel),
        ];
    }

    private function getSubmissions(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, problem_id, verdict, submitted_at, language, source_code
             FROM submissions
             WHERE user_id = :uid
             ORDER BY submitted_at ASC'
        );

        $stmt->execute([
            'uid' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function similarity(string $first, string $second): float
    {
        $first = $this->normalizeCode($first);
        $second = $this->normalizeCode($second);

        if ($first === '' || $second === '') {
            return 0.0;
        }

        similar_text($first, $second, $percent);

        return $percent / 100;
    }

    private function normalizeCode(string $code): string
    {
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = preg_replace('/\/\/.*$/m', '', $code);
        $code = preg_replace('/\s+/', '', $code);

        return strtolower(trim($code));
    }

    private function riskMessage(string $riskLevel): string
    {
        return match ($riskLevel) {
            'HIGH' => 'Several unusual submission patterns were detected.',
            'MEDIUM' => 'Some unusual submission patterns were detected.',
            'LOW' => 'A small number of unusual submission patterns were detected.',
            default => 'No unusual submission patterns were detected.',
        };
    }
}
