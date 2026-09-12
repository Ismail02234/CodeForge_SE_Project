<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class SubmissionAnomalyService
{
    public function analyze(string $userId): array
    {
        $submissions = DB::table('submissions')
            ->select('id', 'problem_id', 'verdict', 'submitted_at', 'language', 'source_code')
            ->where('user_id', $userId)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $rapidAttempts = 0;
        $repeatedFailures = 0;
        $similarityFlags = 0;
        $byProblem = [];

        foreach ($submissions as $submission) {
            $problemId = (string) $submission['problem_id'];
            $byProblem[$problemId][] = $submission;
        }

        /*
         * Preserve Ankita's original rapid-submission logic:
         * each consecutive pair at most 60 seconds apart adds one flag.
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
         * Preserve Ankita's repeated-failure logic:
         * a problem with at least three non-AC submissions adds one flag.
         */
        foreach ($byProblem as $attempts) {
            $failed = 0;

            foreach ($attempts as $attempt) {
                if (strtoupper((string) $attempt['verdict']) !== 'AC') {
                    $failed++;
                }
            }

            if ($failed >= 3) {
                $repeatedFailures++;
            }
        }

        /*
         * Preserve Ankita's similarity logic:
         * compare each source against that user's earlier submissions.
         * A normalized similarity of at least 92% adds one flag.
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

                if ($this->similarity($currentCode, $previousCode) >= 0.92) {
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
        $code = preg_replace('/\/\*.*?\*\//s', '', $code) ?? '';
        $code = preg_replace('/\/\/.*$/m', '', $code) ?? '';
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return strtolower(trim($code));
    }

    private function riskMessage(string $riskLevel): string
    {
        $message = match ($riskLevel) {
            'HIGH' => 'Several unusual submission patterns were detected and may require instructor review.',
            'MEDIUM' => 'Some unusual submission patterns were detected and may require review.',
            'LOW' => 'A small number of unusual submission patterns were detected.',
            default => 'No unusual submission patterns were detected.',
        };

        return $message.' These signals are for review only and are not proof of cheating.';
    }
}
