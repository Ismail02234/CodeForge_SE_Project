<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_problems')) {
            Schema::create('learning_problems', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('learning_module_id', 50)->index();
                $t->string('problem_id', 50)->index();
                $t->enum('stage', ['practice', 'challenge', 'boss'])->index();
                $t->integer('sort_order');
                $t->dateTime('created_at')->useCurrent();

                $t->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_problems')) {
            Schema::dropIfExists('learning_problems');
        }
    }
};
