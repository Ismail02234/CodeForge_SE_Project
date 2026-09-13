<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learning_modules')) {
            DB::statement("ALTER TABLE learning_modules MODIFY COLUMN difficulty ENUM('Beginner','Easy','Medium','Hard') DEFAULT 'Easy'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_modules')) {
            DB::statement("ALTER TABLE learning_modules MODIFY COLUMN difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Easy'");
        }
    }
};
