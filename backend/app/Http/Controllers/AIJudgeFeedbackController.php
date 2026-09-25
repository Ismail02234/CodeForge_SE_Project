<?php

namespace App\Http\Controllers;

use App\Services\AIJudgeFeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIJudgeFeedbackController extends Controller
{
    public function show(
        Request $request,
        string $submissionId,
        AIJudgeFeedbackService $service
    ) {
        $submission = DB::table('submissions as s')
            ->join('problems as p', 'p.id', '=', 's.problem_id')
            ->where('s.id', $submissionId)
            ->where('s.user_id', $request->user()->id)
            ->select(
                's.id',
                's.verdict',
                's.language',
                's.source_code',
                's.failed_test_case',
                'p.title',
                'p.description',
                'p.topic',
                'p.difficulty'
            )
            ->first();

        if (! $submission) {
            return response()->json([
                'message' => 'Submission not found.'
            ], 404);
        }

        if ($submission->verdict === 'AC') {
            return response()->json([
                'available' => false,
                'message' => 'AI feedback is only available for failed submissions.'
            ]);
        }

        $problem = "Title: {$submission->title}\n"
            ."Topic: {$submission->topic}\n"
            ."Difficulty: {$submission->difficulty}\n"
            ."Problem Description:\n{$submission->description}";

        $feedback = $service->analyze(
            $problem,
            $submission->source_code,
            $submission->language,
            $submission->verdict,
            $submission->failed_test_case !== null
                ? (string) $submission->failed_test_case
                : null
        );

        return response()->json([
            'submission_id' => $submission->id,
            'verdict' => $submission->verdict,
            'feedback' => $feedback,
        ]);
    }
}