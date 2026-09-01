<?php

namespace App\Http\Controllers;

use App\Services\PerformanceProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RivalryController extends Controller
{
    public function compare(
        Request $request,
        PerformanceProfileService $performanceProfile
    ): array {
        $leftUserId = (string) (
            $request->query('a') ?: $request->user()->id
        );

        $rightUserId = (string) $request->query('b', '');

        $users = DB::table('users')
            ->orderByDesc('rating')
            ->limit(100)
            ->get([
                'id',
                'username',
                'rating',
                'rank',
            ]);

        $left = $performanceProfile->calculate($leftUserId);

        if ($rightUserId === '') {
            return [
                'users' => $users,
                'left' => $left,
                'right' => null,
            ];
        }

        $right = $performanceProfile->calculate($rightUserId);

        $leftScore = $this->score($left);
        $rightScore = $this->score($right);

        $combinedScore = $leftScore + $rightScore;

        $leftProbability = $combinedScore > 0
            ? (int) round(($leftScore / $combinedScore) * 100)
            : 50;

        return [
            'users' => $users,
            'left' => $left,
            'right' => $right,
            'prediction' => [
                'left_probability' => $leftProbability,
                'right_probability' => 100 - $leftProbability,
                'edge' => $leftScore === $rightScore
                    ? 'even'
                    : ($leftScore > $rightScore ? 'left' : 'right'),
            ],
        ];
    }

    private function score(array $profile): float
    {
        $rating = (float) ($profile['user']['rating'] ?? 1200);

        return ($rating / 50)
            + (($profile['overall'] ?? 0) * 1.5)
            + (($profile['dimensions']['consistency'] ?? 0) * 0.45);
    }
}
