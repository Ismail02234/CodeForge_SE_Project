<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class AIJudgeFeedbackService
{
    public function analyze(
        string $problem,
        string $sourceCode,
        string $language,
        string $verdict,
        ?string $failedTestCase = null
    ): array {
        if ($verdict === 'AC') {
            return [
                'available' => false,
                'message' => 'AI feedback is only available for failed submissions.',
            ];
        }

        $apiKey = trim((string) config('ai_assistant.groq_api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('Groq API key is not configured for AI Judge Feedback.');
        }

        $models = array_values(array_unique(array_filter([
            trim((string) config('ai_assistant.groq_fallback_model', 'openai/gpt-oss-20b')),
            trim((string) config('ai_assistant.model', 'openai/gpt-oss-120b')),
        ])));

        $prompt = <<<PROMPT
You are the AI Judge Feedback assistant for CodeForge, an educational competitive-programming platform.

Analyze this failed submission and help the student understand what to review.

IMPORTANT RULES:
1. NEVER provide complete corrected code.
2. NEVER write a full replacement solution.
3. NEVER include executable code snippets that directly solve the problem.
4. Do NOT reveal the exact final expression, algorithm implementation, or corrected line.
5. Give conceptual and diagnostic guidance only.
6. Hint 3 may identify the likely bug, but must still avoid giving corrected code.
7. If illustrating an idea, use natural-language pseudocode, not executable code.
8. Keep each field concise and educational.
9. Base the feedback on the stated problem, verdict, and submitted code; do not invent test data.

Problem:
{$problem}

Programming Language:
{$language}

Verdict:
{$verdict}

Failed Test Case Number:
{$failedTestCase}

Student Submission:
{$sourceCode}
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'diagnosis' => ['type' => 'string'],
                'hint_1' => ['type' => 'string'],
                'hint_2' => ['type' => 'string'],
                'hint_3' => ['type' => 'string'],
                'explanation' => ['type' => 'string'],
            ],
            'required' => ['diagnosis', 'hint_1', 'hint_2', 'hint_3', 'explanation'],
            'additionalProperties' => false,
        ];

        $lastError = 'Groq did not return usable AI Judge Feedback.';

        foreach ($models as $model) {
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Return only the requested structured JSON feedback.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'codeforge_ai_judge_feedback',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
                'reasoning_effort' => 'medium',
                'max_completion_tokens' => 900,
                'temperature' => 0.2,
                'stream' => false,
            ];

            if (str_starts_with($model, 'openai/gpt-oss-')) {
                $payload['include_reasoning'] = false;
            } elseif (str_starts_with($model, 'qwen/')) {
                $payload['reasoning_format'] = 'hidden';
            }

            try {
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(max(15, min(90, (int) config('ai_assistant.timeout', 35))))
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload);
            } catch (Throwable $exception) {
                Log::warning('AI Judge Groq connection failed.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'model' => $model,
                ]);
                $lastError = 'Could not connect to Groq for AI Judge Feedback.';
                continue;
            }

            if (! $response->successful()) {
                Log::warning('AI Judge Groq request failed.', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 2000),
                    'model' => $model,
                ]);
                $lastError = 'Groq AI Judge request failed with HTTP '.$response->status().'.';
                if (in_array($response->status(), [401, 403], true)) {
                    break;
                }
                continue;
            }

            $text = (string) data_get($response->json(), 'choices.0.message.content', '');
            $feedback = json_decode(trim($text), true);

            if (! is_array($feedback)) {
                $lastError = 'Groq returned invalid structured AI Judge Feedback.';
                continue;
            }

            return [
                'available' => true,
                'provider' => 'Groq',
                'model' => $model,
                'diagnosis' => trim((string) ($feedback['diagnosis'] ?? '')),
                'hint_1' => trim((string) ($feedback['hint_1'] ?? '')),
                'hint_2' => trim((string) ($feedback['hint_2'] ?? '')),
                'hint_3' => trim((string) ($feedback['hint_3'] ?? '')),
                'explanation' => trim((string) ($feedback['explanation'] ?? '')),
            ];
        }

        throw new RuntimeException($lastError);
    }
}