<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_modules')) {
            Schema::create('learning_modules', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('topic', 100)->index();
                $table->text('description')->nullable();
                $table->string('difficulty', 20)->default('Easy')->index();
                $table->integer('estimated_minutes')->default(15);
                $table->integer('xp_reward')->default(50);
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('learning_steps')) {
            Schema::create('learning_steps', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('learning_module_id', 50)->index();
                $table->integer('step_order');
                $table->string('type', 30)->index();
                $table->string('title');
                $table->text('content');
                $table->text('question')->nullable();
                $table->longText('options')->nullable();
                $table->string('correct_answer')->nullable();
                $table->integer('xp_reward')->default(0);
                $table->dateTime('created_at')->useCurrent();

                $table->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('play_challenges')) {
            Schema::create('play_challenges', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('learning_module_id', 50)->index();
                $table->string('type', 40)->index();
                $table->string('title');
                $table->text('instructions');
                $table->longText('config')->nullable();
                $table->integer('xp_reward')->default(0);
                $table->integer('time_limit')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('learning_problems')) {
            Schema::create('learning_problems', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('learning_module_id', 50)->index();
                $table->string('problem_id', 50)->index();
                $table->string('stage', 20)->default('practice')->index();
                $table->integer('sort_order')->default(1);
                $table->dateTime('created_at')->useCurrent();

                $table->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('learning_progress')) {
            Schema::create('learning_progress', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('user_id', 50)->index();
                $table->string('learning_module_id', 50)->index();
                $table->boolean('learn_completed')->default(false)->index();
                $table->boolean('play_completed')->default(false)->index();
                $table->boolean('prove_completed')->default(false)->index();
                $table->json('play_completed_challenges')->nullable();
                $table->integer('learn_score')->default(0);
                $table->integer('play_score')->default(0);
                $table->integer('prove_score')->default(0);
                $table->integer('mastery_score')->default(0);
                $table->integer('attempts')->default(1);
                $table->integer('hints_used')->default(0);
                $table->integer('learn_accuracy')->nullable();
                $table->integer('play_accuracy')->nullable();
                $table->integer('prove_accuracy')->nullable();
                $table->integer('concept_mastery')->nullable();
                $table->string('weakness_signal')->nullable();
                $table->longText('repeated_failed_concepts')->nullable();
                $table->integer('learn_attempts')->default(0);
                $table->integer('play_attempts')->default(0);
                $table->integer('prove_attempts')->default(0);
                $table->integer('learn_correct')->default(0);
                $table->integer('play_correct')->default(0);
                $table->integer('prove_correct')->default(0);
                $table->longText('learn_completed_steps')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->unique(['user_id', 'learning_module_id'], 'learn_progress_user_module_unique');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        } elseif (! Schema::hasColumn('learning_progress', 'play_completed_challenges')) {
            Schema::table('learning_progress', function (Blueprint $table) {
                $table->json('play_completed_challenges')->nullable()->after('play_completed');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('learning_problems');
        Schema::dropIfExists('play_challenges');
        Schema::dropIfExists('learning_steps');
        Schema::dropIfExists('learning_modules');
    }
};
