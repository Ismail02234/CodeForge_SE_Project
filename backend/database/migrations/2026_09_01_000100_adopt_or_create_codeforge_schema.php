<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('universities')) {
            Schema::create('universities', function (Blueprint $t) {
                $t->string('name', 255)->primary();
                $t->string('city', 120)->nullable();
                $t->dateTime('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('username', 100)->unique();
                $t->string('password');
                $t->enum('role', ['user', 'admin'])->default('user');
                $t->integer('rating')->default(1200)->index();
                $t->string('university', 255)->nullable()->index();
                $t->string('rank', 50)->default('Newbie');
                $t->dateTime('created_at')->useCurrent();
                $t->foreign('university')->references('name')->on('universities')->cascadeOnUpdate()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('problems')) {
            Schema::create('problems', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('title');
                $t->string('topic', 100)->index();
                $t->enum('difficulty', ['Easy', 'Medium', 'Hard'])->default('Easy')->index();
                $t->text('description');
                $t->string('tags')->nullable();
                $t->text('starter_code')->nullable();
                $t->dateTime('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('contests')) {
            Schema::create('contests', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('name');
                $t->enum('type', ['Global', 'Local', 'Duel'])->default('Local');
                $t->dateTime('starts_at')->index();
                $t->enum('status', ['Upcoming', 'Active', 'Past'])->default('Upcoming')->index();
                $t->string('created_by', 50)->nullable();
                $t->dateTime('created_at')->useCurrent();
                $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('contest_problems')) {
            Schema::create('contest_problems', function (Blueprint $t) {
                $t->string('contest_id', 50);
                $t->string('problem_id', 50);
                $t->integer('points')->default(100);
                $t->primary(['contest_id', 'problem_id']);
                $t->foreign('contest_id')->references('id')->on('contests')->cascadeOnDelete();
                $t->foreign('problem_id')->references('id')->on('problems')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('contest_participants')) {
            Schema::create('contest_participants', function (Blueprint $t) {
                $t->string('contest_id', 50);
                $t->string('user_id', 50)->index('idx_cpa_user');
                $t->integer('score')->default(0);
                $t->dateTime('joined_at')->useCurrent();
                $t->primary(['contest_id', 'user_id']);
                $t->foreign('contest_id')->references('id')->on('contests')->cascadeOnDelete();
                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('problem_sessions')) {
            Schema::create('problem_sessions', function (Blueprint $t) {
                $t->string('id', 64)->primary();
                $t->string('user_id', 50);
                $t->string('problem_id', 50);
                $t->dateTime('started_at');
                $t->dateTime('completed_at')->nullable();
                $t->integer('solve_time_seconds')->nullable();
                $t->enum('status', ['active', 'solved', 'abandoned'])->default('active');
                $t->dateTime('created_at')->useCurrent();
                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('problem_id')->references('id')->on('problems')->cascadeOnDelete();
                $t->index(['user_id', 'problem_id'], 'idx_ps_user_problem');
                $t->index(['status'], 'idx_ps_status');
                $t->index(['user_id', 'problem_id', 'status', 'started_at'], 'idx_ps_user_problem_status_started');
                $t->index(['status', 'user_id', 'problem_id', 'solve_time_seconds'], 'idx_ps_status_user_problem_solve');
            });
        }

        if (! Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $t) {
                $t->string('id', 64)->primary();
                $t->string('session_id', 64)->nullable();
                $t->string('problem_id', 50);
                $t->string('user_id', 50);
                $t->string('contest_id', 50)->nullable();
                $t->enum('verdict', ['AC', 'WA', 'TLE', 'MLE', 'RE', 'CE']);
                $t->dateTime('submitted_at')->useCurrent();
                $t->integer('elapsed_seconds')->nullable();
                $t->integer('runtime_ms')->nullable();
                $t->integer('memory_kb')->nullable();
                $t->string('language', 50)->default('C++');
                $t->mediumText('source_code')->nullable();
                $t->integer('failed_test_case')->nullable();
                $t->foreign('session_id')->references('id')->on('problem_sessions')->nullOnDelete();
                $t->foreign('problem_id')->references('id')->on('problems')->cascadeOnDelete();
                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('contest_id')->references('id')->on('contests')->nullOnDelete();
                $t->index(['user_id'], 'idx_sub_user');
                $t->index(['problem_id'], 'idx_sub_problem');
                $t->index(['user_id', 'verdict'], 'idx_sub_user_verdict');
                $t->index(['user_id', 'verdict', 'problem_id'], 'idx_sub_user_verdict_problem');
                $t->index(['session_id', 'elapsed_seconds'], 'idx_sub_session_elapsed');
                $t->index(['user_id', 'submitted_at', 'id'], 'idx_sub_user_time');
                $t->index(['problem_id', 'verdict', 'user_id'], 'idx_sub_problem_verdict_user');
                $t->index(['contest_id', 'user_id', 'problem_id', 'verdict'], 'idx_sub_contest_user_problem_verdict');
            });
        }

        if (! Schema::hasTable('ghost_races')) {
            Schema::create('ghost_races', function (Blueprint $t) {
                $t->string('id', 64)->primary();
                $t->string('challenger_id', 50);
                $t->string('ghost_user_id', 50);
                $t->string('problem_id', 50);
                $t->string('ghost_session_id', 64);
                $t->string('challenger_session_id', 64);
                $t->unsignedTinyInteger('playback_speed')->default(4);
                $t->dateTime('started_at')->useCurrent();
                $t->dateTime('finished_at')->nullable();
                $t->enum('result', ['active', 'won', 'lost', 'draw', 'forfeit'])->default('active');
                $t->integer('challenger_time')->nullable();
                $t->integer('ghost_time');
                $t->foreign('challenger_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('ghost_user_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('problem_id')->references('id')->on('problems')->cascadeOnDelete();
                $t->foreign('ghost_session_id')->references('id')->on('problem_sessions')->cascadeOnDelete();
                $t->foreign('challenger_session_id')->references('id')->on('problem_sessions')->cascadeOnDelete();
                $t->index(['challenger_id', 'started_at'], 'idx_gr_challenger');
                $t->index(['challenger_id', 'result', 'started_at'], 'idx_gr_challenger_result_started');
                $t->index(['challenger_session_id', 'result'], 'idx_gr_challenger_session_result');
            });
        }

        if (! Schema::hasTable('sql_challenges')) {
            Schema::create('sql_challenges', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('title');
                $t->text('description');
                $t->enum('difficulty', ['Easy', 'Medium', 'Hard'])->index();
                $t->text('reference_query');
                $t->integer('max_score')->default(1000);
                $t->boolean('order_sensitive')->default(false);
                $t->dateTime('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('sql_battles')) {
            Schema::create('sql_battles', function (Blueprint $t) {
                $t->string('id', 64)->primary();
                $t->string('challenge_id', 50);
                $t->string('player1_id', 50);
                $t->string('player2_id', 50);
                $t->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $t->string('winner_id', 50)->nullable();
                $t->dateTime('created_at')->useCurrent();
                $t->dateTime('completed_at')->nullable();
                $t->foreign('challenge_id')->references('id')->on('sql_challenges')->cascadeOnDelete();
                $t->foreign('player1_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('player2_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('winner_id')->references('id')->on('users')->nullOnDelete();
                $t->index(['player1_id', 'player2_id'], 'idx_sb_players');
                $t->index(['challenge_id', 'status', 'player1_id', 'player2_id'], 'idx_sb_challenge_status_players');
                $t->index(['status'], 'idx_sb_status');
            });
        }

        if (! Schema::hasTable('sql_attempts')) {
            Schema::create('sql_attempts', function (Blueprint $t) {
                $t->string('id', 64)->primary();
                $t->string('battle_id', 64)->nullable();
                $t->string('challenge_id', 50);
                $t->string('user_id', 50);
                $t->text('submitted_query');
                $t->enum('status', ['accepted', 'wrong_answer', 'rejected', 'error']);
                $t->decimal('execution_time_ms', 10, 3)->nullable();
                $t->integer('efficiency_score')->default(0);
                $t->integer('score')->default(0);
                $t->string('feedback', 500)->nullable();
                $t->dateTime('submitted_at')->useCurrent();
                $t->foreign('battle_id')->references('id')->on('sql_battles')->cascadeOnDelete();
                $t->foreign('challenge_id')->references('id')->on('sql_challenges')->cascadeOnDelete();
                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $t->index(['user_id', 'submitted_at'], 'idx_sa_user');
                $t->index(['battle_id', 'user_id'], 'idx_sa_battle_user');
                $t->index(['challenge_id', 'status'], 'idx_sa_challenge_status');
                $t->index(['battle_id', 'status', 'user_id', 'score'], 'idx_sa_battle_status_user_score');
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $t) {
                $t->id();
                $t->string('user_id', 50)->nullable();
                $t->string('action', 100);
                $t->string('details', 500)->nullable();
                $t->dateTime('created_at')->useCurrent();
                $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $t->index(['created_at'], 'idx_activity_created');
                $t->index(['user_id', 'created_at'], 'idx_activity_user_created');
            });
        }

        if (! Schema::hasTable('arena_universities')) {
            Schema::create('arena_universities', function (Blueprint $t) {
                $t->string('name', 120)->primary();
            });
        }
        if (! Schema::hasTable('arena_users')) {
            Schema::create('arena_users', function (Blueprint $t) {
                $t->string('user_id', 20)->primary();
                $t->string('username', 100);
                $t->string('university', 120);
                $t->integer('rating');
                $t->foreign('university')->references('name')->on('arena_universities')->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
        if (! Schema::hasTable('arena_problems')) {
            Schema::create('arena_problems', function (Blueprint $t) {
                $t->string('problem_id', 20)->primary();
                $t->string('title', 120);
                $t->string('topic', 80);
                $t->string('difficulty', 20);
            });
        }
        if (! Schema::hasTable('arena_submissions')) {
            Schema::create('arena_submissions', function (Blueprint $t) {
                $t->string('submission_id', 20)->primary();
                $t->string('user_id', 20)->index();
                $t->string('problem_id', 20)->index();
                $t->string('verdict', 10)->index();
                $t->integer('runtime_ms');
                $t->foreign('user_id')->references('user_id')->on('arena_users')->cascadeOnDelete();
                $t->foreign('problem_id')->references('problem_id')->on('arena_problems')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: this migration can adopt an existing CodeForge database.
    }
};
