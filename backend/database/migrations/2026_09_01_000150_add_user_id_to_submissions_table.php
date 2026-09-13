<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('submissions') &&
            ! Schema::hasColumn('submissions', 'user_id')) {

            Schema::table('submissions', function (Blueprint $table) {
                $table->string('user_id', 50)->nullable(false)->after('problem_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('submissions') &&
            Schema::hasColumn('submissions', 'user_id')) {

            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
