<?php

declare(strict_types=1);

final class SqlJudgeService
{
    private array $allowedTables = [
        'arena_users',
        'arena_universities',
        'arena_problems',
        'arena_submissions',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function validate(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [false, 'Write a SELECT query before submitting.'];
        }
        if (strlen($query) > 3000) {
            return [false, 'Query is too long for the arena.'];
        }

        $withoutTrailingSemicolon = preg_replace('/;\s*$/', '', $query) ?? $query;
        if (str_contains($withoutTrailingSemicolon, ';')) {
            return [false, 'Only one SQL statement is allowed.'];
        }

        if (!preg_match('/^(SELECT|WITH)\b/i', ltrim($withoutTrailingSemicolon))) {
            return [false, 'Only SELECT queries (including CTEs) are allowed.'];
        }

        if (preg_match('/(--|#|\/\*)/', $withoutTrailingSemicolon)) {
            return [false, 'SQL comments are disabled in the arena.'];
        }

        $blocked = [
            'INSERT','UPDATE','DELETE','DROP','ALTER','CREATE','TRUNCATE','REPLACE',
            'GRANT','REVOKE','CALL','PROCEDURE','FUNCTION','TRIGGER','EVENT',
            'LOAD_FILE','LOAD DATA','OUTFILE','DUMPFILE','INFILE','SLEEP','BENCHMARK',
            'INFORMATION_SCHEMA','PERFORMANCE_SCHEMA','MYSQL.','SYS.'
        ];
        foreach ($blocked as $keyword) {
            if (stripos($withoutTrailingSemicolon, $keyword) !== false) {
                return [false, "The arena blocks {$keyword} for safety."];
            }
        }

        if (preg_match('/\bINTO\b/i', $withoutTrailingSemicolon)) {
            return [false, 'SELECT ... INTO is not allowed.'];
        }

        $cteNames = [];
        if (preg_match_all('/(?:WITH|,)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+AS\s*\(/i', $withoutTrailingSemicolon, $cteMatches)) {
            $cteNames = array_map('strtolower', $cteMatches[1]);
        }

        $tables = [];
        if (preg_match_all('/\b(?:FROM|JOIN)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $withoutTrailingSemicolon, $matches)) {
            $tables = array_map('strtolower', $matches[1]);
        }

        foreach ($tables as $table) {
            if (in_array($table, $cteNames, true)) {
                continue;
            }
            if (!in_array($table, $this->allowedTables, true)) {
                return [false, "Table '{$table}' is outside the SQL Arena sandbox."];
            }
        }

        if ($tables === []) {
            return [false, 'Your query must read from at least one SQL Arena table.'];
        }

        return [true, 'Query passed the read-only sandbox checks.'];
    }

    public function judge(array $challenge, string $query): array
    {
        [$valid, $validationMessage] = $this->validate($query);
        if (!$valid) {
            return [
                'status' => 'rejected',
                'correct' => false,
                'score' => 0,
                'execution_time_ms' => null,
                'efficiency_score' => 0,
                'feedback' => $validationMessage,
                'rows' => [],
            ];
        }

        try {
            $referenceStart = microtime(true);
            $referenceRows = $this->pdo->query((string) $challenge['reference_query'])->fetchAll();
            $referenceMs = max(0.001, (microtime(true) - $referenceStart) * 1000);

            $start = microtime(true);
            $userRows = $this->pdo->query($query)->fetchAll();
            $executionMs = max(0.001, (microtime(true) - $start) * 1000);

            $orderSensitive = (bool) ($challenge['order_sensitive'] ?? false);
            $correct = $this->canonicalize($userRows, $orderSensitive) === $this->canonicalize($referenceRows, $orderSensitive);

            $efficiency = $this->efficiencyScore($query, (string) $challenge['reference_query']);
            $speedBonus = $this->speedBonus($executionMs, $referenceMs);
            $score = $correct ? min((int) $challenge['max_score'], 700 + $speedBonus + (int) round($efficiency * 1.5)) : 0;

            return [
                'status' => $correct ? 'accepted' : 'wrong_answer',
                'correct' => $correct,
                'score' => $score,
                'execution_time_ms' => round($executionMs, 3),
                'efficiency_score' => $efficiency,
                'feedback' => $correct
                    ? 'Accepted. Your result set matches the expected answer.'
                    : 'Wrong answer. The query ran safely, but its result set does not match the challenge.',
                'rows' => array_slice($userRows, 0, 30),
            ];
        } catch (PDOException $error) {
            return [
                'status' => 'error',
                'correct' => false,
                'score' => 0,
                'execution_time_ms' => null,
                'efficiency_score' => 0,
                'feedback' => 'SQL error: ' . $this->cleanDbMessage($error->getMessage()),
                'rows' => [],
            ];
        }
    }

    private function canonicalize(array $rows, bool $orderSensitive): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            $clean = [];
            foreach ($row as $key => $value) {
                $clean[(string) $key] = $value === null ? null : (string) $value;
            }
            $normalized[] = $clean;
        }

        if (!$orderSensitive) {
            usort($normalized, static fn (array $a, array $b): int => json_encode($a) <=> json_encode($b));
        }
        return $normalized;
    }

    private function efficiencyScore(string $userQuery, string $referenceQuery): int
    {
        try {
            $userRows = $this->estimatedRows($userQuery);
            $referenceRows = max(1, $this->estimatedRows($referenceQuery));
            if ($userRows <= 0) {
                return 80;
            }
            $ratio = $referenceRows / max(1, $userRows);
            return (int) round(max(35, min(100, $ratio * 100)));
        } catch (Throwable) {
            return 70;
        }
    }

    private function estimatedRows(string $query): int
    {
        $rows = $this->pdo->query('EXPLAIN ' . $query)->fetchAll();
        $total = 0;
        foreach ($rows as $row) {
            $total += max(0, (int) ($row['rows'] ?? 0));
        }
        return $total;
    }

    private function speedBonus(float $userMs, float $referenceMs): int
    {
        $ratio = $userMs / max(0.001, $referenceMs);
        return match (true) {
            $ratio <= 1.25 => 150,
            $ratio <= 2.0 => 130,
            $ratio <= 4.0 => 100,
            $ratio <= 8.0 => 70,
            default => 40,
        };
    }

    private function cleanDbMessage(string $message): string
    {
        $message = preg_replace('/SQLSTATE\[[^\]]+\]:?\s*/', '', $message) ?? $message;
        return mb_substr($message, 0, 350);
    }
}
