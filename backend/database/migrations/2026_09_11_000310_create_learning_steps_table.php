<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_steps')) {
            Schema::create('learning_steps', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('learning_module_id', 50)->index();
                $t->integer('step_order');
                $t->enum('type', ['explanation', 'mcq', 'prediction', 'visual', 'code_trace'])->index();
                $t->string('title');
                $t->text('content');
                $t->text('question')->nullable();
                $t->longText('options')->nullable();
                $t->string('correct_answer')->nullable();
                $t->integer('xp_reward');
                $t->dateTime('created_at')->useCurrent();

                $t->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_steps')) {
            Schema::dropIfExists('learning_steps');
        }
    }
};
