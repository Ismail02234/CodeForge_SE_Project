<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use App\Services\PerformanceProfileService;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(string $id, PerformanceProfileService $PROFILE, GamificationService $game)
    {
        $user = DB::table('users')->where('id', $id)->first(['id', 'username', 'rating', 'university', 'rank', 'role', 'created_at']);
        abort_if(! $user, 404, 'User not found.');

        $stats = DB::table('submissions')->where('user_id', $id)
            ->selectRaw("COUNT(*) submissions,
                SUM(CASE WHEN verdict='AC' THEN 1 ELSE 0 END) accepted,
                COUNT(DISTINCT CASE WHEN verdict='AC' THEN problem_id END) solved")
            ->first();

        return [
            'user' => $user,
            'stats' => $stats,
            'PROFILE' => $profile->calculate($id),
            'gamification' => $game->forUser($id),
            'recent_submissions' => DB::table('submissions as s')->join('problems as p', 'p.id', '=', 's.problem_id')
                ->where('s.user_id', $id)->orderByDesc('s.submitted_at')->limit(12)
                ->get(['s.id', 'p.title', 'p.topic', 'p.difficulty', 's.verdict', 's.language', 's.runtime_ms', 's.submitted_at']),
        ];
    }
}
