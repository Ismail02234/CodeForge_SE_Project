<?php

namespace App\Http\Controllers;

use App\Services\SubmissionAnomalyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionAnomalyController extends Controller
{
    public function me(
        Request $request,
        SubmissionAnomalyService $service
    ): JsonResponse {
        return response()->json(
            $service->analyze((string) $request->user()->id)
        );
    }

    public function show(
        string $userId,
        SubmissionAnomalyService $service
    ): JsonResponse {
        return response()->json(
            $service->analyze($userId)
        );
    }
}
