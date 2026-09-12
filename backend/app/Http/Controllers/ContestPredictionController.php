<?php

namespace App\Http\Controllers;

use App\Services\ContestWinProbabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ContestPredictionController extends Controller
{
    public function show(
        Request $request,
        ContestWinProbabilityService $predictions
    ): array|JsonResponse {
        try {
            return $predictions->data(
                $request->filled('contest_id')
                    ? (string) $request->string('contest_id')
                    : null,
                $request->filled('a')
                    ? (string) $request->string('a')
                    : null,
                $request->filled('b')
                    ? (string) $request->string('b')
                    : null
            );
        } catch (RuntimeException $error) {
            return response()->json([
                'message' => $error->getMessage(),
            ], 422);
        }
    }
}
