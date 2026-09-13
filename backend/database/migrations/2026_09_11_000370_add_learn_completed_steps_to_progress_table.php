<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            Schema::table('learning_progress', function (Blueprint $t) {
                $t->longText('learn_completed_steps')->nullable()->after('prove_correct');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            Schema::table('learning_progress', function (Blueprint $t) {
                $t->dropColumn('learn_completed_steps');
            });
        }
    }
};
