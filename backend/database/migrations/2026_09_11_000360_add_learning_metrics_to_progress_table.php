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
                $t->integer('learn_accuracy')->nullable()->after('hints_used');
                $t->integer('play_accuracy')->nullable()->after('learn_accuracy');
                $t->integer('prove_accuracy')->nullable()->after('play_accuracy');
                $t->integer('concept_mastery')->nullable()->after('prove_accuracy');
                $t->string('weakness_signal')->nullable()->after('concept_mastery');
                $t->longText('repeated_failed_concepts')->nullable()->after('weakness_signal');
                $t->integer('learn_attempts')->default(0)->after('repeated_failed_concepts');
                $t->integer('play_attempts')->default(0)->after('learn_attempts');
                $t->integer('prove_attempts')->default(0)->after('play_attempts');
                $t->integer('learn_correct')->default(0)->after('prove_attempts');
                $t->integer('play_correct')->default(0)->after('learn_correct');
                $t->integer('prove_correct')->default(0)->after('play_correct');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            Schema::table('learning_progress', function (Blueprint $t) {
                $t->dropColumn([
                    'learn_accuracy', 'play_accuracy', 'prove_accuracy',
                    'concept_mastery', 'weakness_signal', 'repeated_failed_concepts',
                    'learn_attempts', 'play_attempts', 'prove_attempts',
                    'learn_correct', 'play_correct', 'prove_correct',
                ]);
            });
        }
    }
};
