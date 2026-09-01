<?php

namespace App\Http\Controllers;

use App\Services\CodeDnaService;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request, CodeDnaService $dna, GamificationService $game)
    {
        $uid = $request->user()->id;
        $stats = [
            'rating' => (int) $request->user()->rating,
            'solved' => (int) DB::table('submissions')->where('user_id', $uid)->where('verdict', 'AC')->distinct('problem_id')->count('problem_id'),
            'submissions' => (int) DB::table('submissions')->where('user_id', $uid)->count(),
            'contests' => (int) DB::table('contest_participants')->where('user_id', $uid)->count('contest_id'),
        ];
        $weak = DB::table('problems as p')
            ->leftJoin('submissions as s', function ($j) use ($uid) {
                $j->on('s.problem_id', '=', 'p.id')->where('s.user_id', $uid)->where('s.verdict', 'AC');
            })
            ->groupBy('p.topic')->selectRaw('p.topic, COUNT(DISTINCT s.problem_id) solved, COUNT(DISTINCT p.id) total')
            ->orderBy('solved')->orderByDesc('total')->first();
        $recommended = [];
        if ($weak) {
            $recommended = DB::table('problems as p')->where('p.topic', $weak->topic)
                ->whereNotIn('p.id', DB::table('submissions')->select('problem_id')->where('user_id', $uid)->where('verdict', 'AC'))
                ->limit(4)->get()->all();
        }

        return ['user' => $request->user(), 'stats' => $stats, 'weakest_topic' => $weak, 'recommended' => $recommended,
            'recent' => DB::table('submissions as s')->join('problems as p', 'p.id', '=', 's.problem_id')->where('s.user_id', $uid)
                ->orderByDesc('s.submitted_at')->limit(8)->get(['s.id', 'p.title', 's.verdict', 's.language', 's.submitted_at']),
            'dna' => $dna->calculate($uid), 'gamification' => $game->forUser($uid)];
    }
}
