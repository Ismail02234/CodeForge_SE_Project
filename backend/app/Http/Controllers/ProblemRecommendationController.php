<?php

namespace App\Http\Controllers;

use App\Services\ProblemRecommendationService;
use Illuminate\Http\Request;

final class ProblemRecommendationController extends Controller
{
    public function me(
        Request $request,
        ProblemRecommendationService $recommendations
    ): array {
        $limit = (int) $request->integer('limit', 3);

        return $recommendations->forUser(
            (string) $request->user()->id,
            $limit
        );
    }
}
