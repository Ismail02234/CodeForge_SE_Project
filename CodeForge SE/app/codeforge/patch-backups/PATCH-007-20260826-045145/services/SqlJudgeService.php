<?php

declare(strict_types=1);

final class SqlJudgeService
{
    private const MAX_QUERY_LENGTH = 3000;
    private const MAX_RESULT_ROWS = 1000;
    private const MAX_STATEMENT_SECONDS = 1;

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
        if (strlen($query) > self::MAX_QUERY_LENGTH) {
            return [false, 'Query is too long for the arena.'];
        }

        $sql = preg_replace('/;\s*$/', '', $query) ?? $query;
        if (str_contains($sql, ';')) {
            return [false, 'Only one SQL statement is allowed.'];
        }

        if (!preg_match('/^(SELECT|WITH)\b/i', ltrim($sql))) {
            return [false, 'Only SELECT queries (including non-recursive CTEs) are allowed.'];
        }

        if (preg_match('/(--|#|\/\*)/', $sql)) {
            return [false, 'SQL comments are disabled in the arena.'];
        }

        if (preg_match('/\bWITH\s+RECURSIVE\b/i', $sql)) {
            return [false, 'Recursive CTEs are disabled in the arena.'];
        }

        if (str_contains($sql, '@')) {
            return [false, 'SQL variables are disabled in the arena.'];
        }

        $blocked = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'REPLACE',
            'GRANT', 'REVOKE', 'CALL', 'PROCEDURE', 'FUNCTION', 'TRIGGER', 'EVENT',
            'LOAD_FILE', 'LOAD DATA', 'OUTFILE', 'DUMPFILE', 'INFILE', 'SLEEP', 'BENCHMARK',
            'INFORMATION_SCHEMA', 'PERFORMANCE_SCHEMA', 'MYSQL.', 'SYS.',
            'GET_LOCK', 'RELEASE_LOCK', 'IS_FREE_LOCK', 'CURRENT_USER', 'SESSION_USER',
            'SYSTEM_USER', 'DATABASE(', 'SCHEMA(', 'VERSION(',
        ];

        foreach ($blocked as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return [false, "The arena blocks {$keyword} for safety."];
            }
        }

        if (preg_match('/\bINTO\b/i', $sql)) {
            return [false, 'SELECT ... INTO is not allowed.'];
        }

        $cteNames = [];
        if (preg_match_all('/(?:WITH|,)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+AS\s*\(/i', $sql, $cteMatches)) {
            $cteNames = array_map('strtolower', $cteMatches[1]);
        }

        $tables = [];
        if (preg_match_all('/\b(?:FROM|JOIN)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $matches)) {
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
            return $this->failure('rejected', $validationMessage);
        }

        try {
            return $this->withStatementTimeout(function () use ($challenge, $query): array {
                $referenceStart = microtime(true);
                $referenceRows = $this->fetchRowsLimited((string) $challenge['reference_query']);
                $referenceMs = max(0.001, (microtime(true) - $referenceStart) * 1000);

                $start = microtime(true);
                $userRows = $this->fetchRowsLimited($query);
                $executionMs = max(0.001, (microtime(true) - $start) * 1000);

                $orderSensitive = (bool) ($challenge['order_sensitive'] ?? false);
                $correct = $this->canonicalize($userRows, $orderSensitive)
                    === $this->canonicalize($referenceRows, $orderSensitive);

                $efficiency = $this->efficiencyScore($query, (string) $challenge['reference_query']);
                $speedBonus = $this->speedBonus($executionMs, $referenceMs);
                $score = $correct
                    ? min((int) $challenge['max_score'], 700 + $speedBonus + (int) round($efficiency * 1.5))
                    : 0;

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
            });
        } catch (RuntimeException $error) {
            return $this->failure('rejected', $error->getMessage());
        } catch (PDOException $error) {
            return $this->failure('error', 'SQL error: ' . $this->cleanDbMessage($error->getMessage()));
        }
    }

    private function fetchRowsLimited(string $query): array
    {
        $stmt = $this->pdo->query($query);
        $rows = [];

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $rows[] = $row;
            if (count($rows) > self::MAX_RESULT_ROWS) {
                $stmt->closeCursor();
                throw new RuntimeException(
                    'Result set is too large for the arena. Refine the query to return at most '
                    . self::MAX_RESULT_ROWS . ' rows.'
                );
            }
        }

        return $rows;
    }

    private function withStatementTimeout(callable $callback): mixed
    {
        $timeoutEnabled = false;

        try {
            // MariaDB supports max_statement_time. If the server is MySQL and
            // does not expose it, the safety validator/result cap still apply.
            $this->pdo->exec('SET SESSION max_statement_time = ' . self::MAX_STATEMENT_SECONDS);
            $timeoutEnabled = true;
        } catch (PDOException) {
            $timeoutEnabled = false;
        }

        try {
            return $callback();
        } finally {
            if ($timeoutEnabled) {
                try {
                    $this->pdo->exec('SET SESSION max_statement_time = 0');
                } catch (PDOException) {
                    // Request is ending; do not mask the judge result.
                }
            }
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
            usort(
                $normalized,
                static fn (array $a, array $b): int =>
                    json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    <=> json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
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
        $rows = $this->pdo->query('EXPLAIN ' . $query)->fetchAll(PDO::FETCH_ASSOC);
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

    private function failure(string $status, string $feedback): array
    {
        return [
            'status' => $status,
            'correct' => false,
            'score' => 0,
            'execution_time_ms' => null,
            'efficiency_score' => 0,
            'feedback' => $feedback,
            'rows' => [],
        ];
    }

    private function cleanDbMessage(string $message): string
    {
        $message = preg_replace('/SQLSTATE\[[^\]]+\]:?\s*/', '', $message) ?? $message;
        return mb_substr($message, 0, 350);
    }
}
