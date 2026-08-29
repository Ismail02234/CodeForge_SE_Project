<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/core/bootstrap.php';

function tableExistsForOptimization(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function indexExistsForOptimization(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index'
    );
    $stmt->execute(['table' => $table, 'index' => $index]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureIndex(PDO $pdo, string $table, string $index, array $columns): void
{
    if (!tableExistsForOptimization($pdo, $table)) {
        echo "[SKIP] {$table} does not exist\n";
        return;
    }
    if (indexExistsForOptimization($pdo, $table, $index)) {
        echo "[OK]   {$table}.{$index}\n";
        return;
    }

    $quoted = array_map(
        static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`',
        $columns
    );
    $sql = sprintf(
        'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
        str_replace('`', '``', $table),
        str_replace('`', '``', $index),
        implode(', ', $quoted)
    );
    $pdo->exec($sql);
    echo "[ADD]  {$table}.{$index}\n";
}

$indexes = [
    ['contest_participants', 'idx_cpa_user', ['user_id']],
    ['problem_sessions', 'idx_ps_user_problem_status_started', ['user_id', 'problem_id', 'status', 'started_at']],
    ['problem_sessions', 'idx_ps_status_user_problem_solve', ['status', 'user_id', 'problem_id', 'solve_time_seconds']],
    ['submissions', 'idx_sub_user_time', ['user_id', 'submitted_at', 'id']],
    ['submissions', 'idx_sub_user_verdict_problem', ['user_id', 'verdict', 'problem_id']],
    ['submissions', 'idx_sub_problem_verdict_user', ['problem_id', 'verdict', 'user_id']],
    ['submissions', 'idx_sub_contest_user_problem_verdict', ['contest_id', 'user_id', 'problem_id', 'verdict']],
    ['ghost_races', 'idx_gr_challenger_result_started', ['challenger_id', 'result', 'started_at']],
    ['ghost_races', 'idx_gr_challenger_session_result', ['challenger_session_id', 'result']],
    ['sql_battles', 'idx_sb_challenge_status_players', ['challenge_id', 'status', 'player1_id', 'player2_id']],
    ['sql_battles', 'idx_sb_player1_created', ['player1_id', 'created_at']],
    ['sql_battles', 'idx_sb_player2_created', ['player2_id', 'created_at']],
    ['sql_attempts', 'idx_sa_challenge_status', ['challenge_id', 'status']],
    ['sql_attempts', 'idx_sa_battle_status_user_score', ['battle_id', 'status', 'user_id', 'score']],
    ['activity_logs', 'idx_activity_user_created', ['user_id', 'created_at']],
];

foreach ($indexes as [$table, $index, $columns]) {
    ensureIndex($pdo, $table, $index, $columns);
}

foreach (['users', 'problems', 'problem_sessions', 'submissions', 'ghost_races', 'sql_battles', 'sql_attempts'] as $table) {
    if (tableExistsForOptimization($pdo, $table)) {
        try {
            $pdo->exec("ANALYZE TABLE `{$table}`");
            echo "[ANALYZE] {$table}\n";
        } catch (PDOException $error) {
            echo "[WARN] Could not analyze {$table}: {$error->getMessage()}\n";
        }
    }
}

echo "Database optimization complete.\n";
