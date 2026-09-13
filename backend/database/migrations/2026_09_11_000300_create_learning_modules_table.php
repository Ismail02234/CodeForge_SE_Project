<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_modules')) {
            Schema::create('learning_modules', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('title');
                $t->string('slug')->unique();
                $t->string('topic', 100)->index();
                $t->text('description')->nullable();
                $t->enum('difficulty', ['Beginner', 'Easy', 'Medium', 'Hard'])->default('Easy')->index();
                $t->integer('estimated_minutes')->default(15);
                $t->integer('xp_reward')->default(50);
                $t->boolean('is_active')->default(true)->index();
                $t->dateTime('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_modules')) {
            Schema::dropIfExists('learning_modules');
        }
    }
};
