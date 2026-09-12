<?php

namespace App\Http\Controllers;

use App\Services\SubmissionAnomalyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionAnomalyController extends Controller
{
    public function me(Request $request, SubmissionAnomalyService $service)
    {
        $userId = $request->user()->id;

        $pdo = DB::connection()->getPdo();

        $analyzer = new SubmissionAnomalyService($pdo);

        return response()->json(
            $analyzer->analyze($userId)
        );
    }

    public function show(
        string $userId,
        SubmissionAnomalyService $service
    ) {
        return response()->json(
            $service->analyze($userId)
        );
    }
}