<?php

namespace App\Http\Controllers;

use App\Services\PerformanceProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RivalryController extends Controller
{
    public function compare(Request $request, PerformanceProfileService $PROFILE)
    {
        $a = (string) ($request->query('a') ?: $request->user()->id);
        $b = (string) $request->query('b', '');
        $users = DB::table('users')->orderByDesc('rating')->limit(100)->get(['id', 'username', 'rating', 'rank']);
        if ($b === '') {
            return ['users' => $users, 'left' => $profile->calculate($a), 'right' => null];
        }

        $left = $profile->calculate($a);
        $right = $profile->calculate($b);
        $scoreA = $this->score($left);
        $scoreB = $this->score($right);
        $probA = $scoreA + $scoreB > 0 ? (int) round(($scoreA / ($scoreA + $scoreB)) * 100) : 50;

        return [
            'users' => $users, 'left' => $left, 'right' => $right,
            'prediction' => [
                'left_probability' => $probA,
                'right_probability' => 100 - $probA,
                'edge' => $scoreA === $scoreB ? 'even' : ($scoreA > $scoreB ? 'left' : 'right'),
            ],
        ];
    }

    private function score(array $PROFILE): float
    {
        $rating = (float) ($PROFILE['user']['rating'] ?? 1200);

        return ($rating / 50) + (($PROFILE['overall'] ?? 0) * 1.5) + (($PROFILE['dimensions']['consistency'] ?? 0) * .45);
    }
}
