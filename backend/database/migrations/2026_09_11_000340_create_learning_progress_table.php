<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            Schema::create('learning_progress', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('user_id', 50)->index();
                $t->string('learning_module_id', 50)->index();
                $t->boolean('learn_completed')->default(false)->index();
                $t->boolean('play_completed')->default(false)->index();
                $t->boolean('prove_completed')->default(false)->index();
                $t->integer('learn_score')->nullable();
                $t->integer('play_score')->nullable();
                $t->integer('prove_score')->nullable();
                $t->integer('mastery_score')->nullable();
                $t->integer('attempts')->default(1);
                $t->integer('hints_used')->default(0);
                $t->integer('learn_accuracy')->nullable();
                $t->integer('play_accuracy')->nullable();
                $t->integer('prove_accuracy')->nullable();
                $t->integer('concept_mastery')->nullable();
                $t->string('weakness_signal')->nullable();
                $t->longText('repeated_failed_concepts')->nullable();
                $t->integer('learn_attempts')->default(0);
                $t->integer('play_attempts')->default(0);
                $t->integer('prove_attempts')->default(0);
                $t->integer('learn_correct')->default(0);
                $t->integer('play_correct')->default(0);
                $t->integer('prove_correct')->default(0);
                $t->longText('learn_completed_steps')->nullable();
                $t->dateTime('started_at')->nullable();
                $t->dateTime('completed_at')->nullable();
                $t->dateTime('created_at')->useCurrent();

                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $t->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_progress')) {
            Schema::dropIfExists('learning_progress');
        }
    }
};
