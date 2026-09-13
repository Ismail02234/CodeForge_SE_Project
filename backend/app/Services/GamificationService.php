<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class GamificationService
{
    public function forUser(string $userId): array
    {
        $submissions = (int) DB::table('submissions')->where('user_id', $userId)->count();
        $contests = (int) DB::table('contest_participants')->where('user_id', $userId)->count();
        $sqlAttempts = (int) DB::table('sql_attempts')->where('user_id', $userId)->count();
        $moduleXp = (int) DB::table('learning_progress as lp')
            ->join('learning_modules as lm', 'lm.id', '=', 'lp.learning_module_id')
            ->where('lp.user_id', $userId)
            ->where('lp.prove_completed', true)
            ->where('lp.learn_completed', true)
            ->where('lp.play_completed', true)
            ->sum('lm.xp_reward');
        $xp = ($submissions * 50) + ($contests * 100) + ($sqlAttempts * 40) + $moduleXp;

        $levels = [
            [0, 'Rookie'], [500, 'Coder'], [1500, 'Specialist'], [3000, 'Expert'],
            [6000, 'Master'], [10000, 'Legend'],
        ];
        $current = $levels[0];
        $next = null;
        foreach ($levels as $i => $level) {
            if ($xp >= $level[0]) {
                $current = $level;
            } elseif ($next === null) {
                $next = $level;
            }
        }
        $floor = $current[0];
        $ceil = $next[0] ?? max(12000, $xp);
        $progress = $ceil > $floor ? (int) round((($xp - $floor) / ($ceil - $floor)) * 100) : 100;

        $badges = [];
        if ($submissions >= 10) {
            $badges[] = 'Persistent';
        }
        if ($submissions >= 50) {
            $badges[] = 'Grinder';
        }
        if ($contests >= 3) {
            $badges[] = 'Competitor';
        }
        if ($sqlAttempts >= 5) {
            $badges[] = 'Query Fighter';
        }
        if ($xp >= 10000) {
            $badges[] = 'Legend';
        }

        return compact('xp', 'submissions', 'contests', 'sqlAttempts') + [
            'level' => $current[1], 'next_level' => $next[1] ?? null, 'progress' => max(0, min(100, $progress)), 'badges' => $badges,
        ];
    }
}
