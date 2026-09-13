<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'submissions' => [
                'idx_sub_user_verdict_problem' => [
                    'user_id',
                    'verdict',
                    'problem_id',
                ],

                'idx_sub_session_elapsed' => [
                    'session_id',
                    'elapsed_seconds',
                ],

                'idx_sub_contest_user_problem_verdict' => [
                    'contest_id',
                    'user_id',
                    'problem_id',
                    'verdict',
                ],
            ],

            'problem_sessions' => [
                'idx_ps_user_problem_status_started' => [
                    'user_id',
                    'problem_id',
                    'status',
                    'started_at',
                ],

                'idx_ps_status_user_problem_solve' => [
                    'status',
                    'user_id',
                    'problem_id',
                    'solve_time_seconds',
                ],
            ],

            'ghost_races' => [
                'idx_gr_challenger_result_started' => [
                    'challenger_id',
                    'result',
                    'started_at',
                ],
            ],

            'sql_attempts' => [
                'idx_sa_battle_status_user_score' => [
                    'battle_id',
                    'status',
                    'user_id',
                    'score',
                ],
            ],

            'sql_battles' => [
                'idx_sb_challenge_status_players' => [
                    'challenge_id',
                    'status',
                    'player1_id',
                    'player2_id',
                ],

                'idx_sb_player1_created' => [
                    'player1_id',
                    'created_at',
                ],

                'idx_sb_player2_created' => [
                    'player2_id',
                    'created_at',
                ],
            ],

            'activity_logs' => [
                'idx_activity_user_created' => [
                    'user_id',
                    'created_at',
                ],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            // Don't touch tables that don't exist.
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $name => $columns) {

                // Don't create an index if any required column
                // is missing from the existing database.
                $allColumnsExist = true;

                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $allColumnsExist = false;
                        break;
                    }
                }

                if (! $allColumnsExist) {
                    continue;
                }

                // Don't create duplicate indexes.
                if ($this->indexExists($table, $name)) {
                    continue;
                }

                $quotedColumns = implode(
                    ', ',
                    array_map(
                        fn ($column) => "`{$column}`",
                        $columns
                    )
                );

                DB::statement(
                    "CREATE INDEX `{$name}` ON `{$table}` ({$quotedColumns})"
                );
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }

    private function indexExists(string $table, string $name): bool
    {
        $rows = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$name]
        );

        return ! empty($rows);
    }
};
