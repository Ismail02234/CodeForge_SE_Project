<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_challenges', function (Blueprint $t) {
            $t->enum('type', ['coding', 'debug', 'optimize', 'fill_blank', 'trace', 'binary_search'])
              ->default('coding')
              ->change();
        });
    }

    public function down(): void
    {
        Schema::table('play_challenges', function (Blueprint $t) {
            $t->enum('type', ['coding', 'debug', 'optimize', 'fill_blank', 'trace'])
              ->default('coding')
              ->change();
        });
    }
};
