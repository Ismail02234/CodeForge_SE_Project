<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('play_challenges')) {
            Schema::create('play_challenges', function (Blueprint $t) {
                $t->string('id', 50)->primary();
                $t->string('learning_module_id', 50)->index();
                $t->enum('type', ['coding', 'debug', 'optimize', 'fill_blank', 'trace'])->index();
                $t->string('title');
                $t->text('instructions');
                $t->longText('config')->nullable();
                $t->integer('xp_reward');
                $t->integer('time_limit')->nullable();
                $t->dateTime('created_at')->useCurrent();

                $t->foreign('learning_module_id')->references('id')->on('learning_modules')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('play_challenges')) {
            Schema::dropIfExists('play_challenges');
        }
    }
};
