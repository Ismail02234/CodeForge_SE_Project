<?php

namespace App\Http\Controllers;

use App\Services\GamificationService;
use App\Services\PerformanceProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(
        Request $request,
        PerformanceProfileService $performanceProfile,
        GamificationService $gamification
    ): array {
        $user = $request->user();
        $userId = (string) $user->id;

        $stats = [
            'rating' => (int) $user->rating,
            'solved' => (int) DB::table('submissions')
                ->where('user_id', $userId)
                ->where('verdict', 'AC')
                ->distinct()
                ->count('problem_id'),
            'submissions' => (int) DB::table('submissions')
                ->where('user_id', $userId)
                ->count(),
            'contests' => (int) DB::table('contest_participants')
                ->where('user_id', $userId)
                ->count('contest_id'),
        ];

        $weakestTopic = DB::table('problems as p')
            ->leftJoin('submissions as s', function ($join) use ($userId): void {
                $join->on('s.problem_id', '=', 'p.id')
                    ->where('s.user_id', $userId)
                    ->where('s.verdict', 'AC');
            })
            ->groupBy('p.topic')
            ->selectRaw(
                'p.topic,
                 COUNT(DISTINCT s.problem_id) AS solved,
                 COUNT(DISTINCT p.id) AS total'
            )
            ->orderBy('solved')
            ->orderByDesc('total')
            ->first();

        $recommended = [];

        if ($weakestTopic) {
            $solvedProblemIds = DB::table('submissions')
                ->select('problem_id')
                ->where('user_id', $userId)
                ->where('verdict', 'AC');

            $recommended = DB::table('problems as p')
                ->where('p.topic', $weakestTopic->topic)
                ->whereNotIn('p.id', $solvedProblemIds)
                ->limit(4)
                ->get()
                ->all();
        }

        $recent = DB::table('submissions as s')
            ->join('problems as p', 'p.id', '=', 's.problem_id')
            ->where('s.user_id', $userId)
            ->orderByDesc('s.submitted_at')
            ->limit(8)
            ->get([
                's.id',
                'p.title',
                's.verdict',
                's.language',
                's.submitted_at',
            ]);

        return [
            'user' => $user,
            'stats' => $stats,
            'weakest_topic' => $weakestTopic,
            'recommended' => $recommended,
            'recent' => $recent,
            'performance_profile' => $performanceProfile->calculate($userId),
            'gamification' => $gamification->forUser($userId),
        ];
    }
}
