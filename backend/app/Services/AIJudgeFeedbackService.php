<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

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

        $model = 'qwen2.5-coder:1.5b';
        $ollamaUrl = 'http://127.0.0.1:11434/api/generate';

        $prompt = <<<PROMPT
You are an AI coding judge assistant for an educational programming platform.

Analyze the student's failed programming submission.

IMPORTANT RULES:
1. NEVER provide complete corrected code.
2. NEVER write a full replacement solution.
3. NEVER include executable code snippets that directly solve the problem.
4. Do NOT reveal the exact final expression, algorithm implementation, or corrected line.
5. Give conceptual and diagnostic guidance only.
6. Hint 3 may identify the likely bug, but must still avoid giving the corrected code.
7. If you need to illustrate an idea, use natural-language pseudocode, not executable code.
8. Keep each hint short and educational.
Return exactly this structure:

{
  "diagnosis": "short likely diagnosis",
  "hint_1": "conceptual hint",
  "hint_2": "algorithmic hint",
  "hint_3": "specific bug hint without corrected code",
  "explanation": "short explanation of what the student should review"
}

Problem:
{$problem}

Programming Language:
{$language}

Verdict:
{$verdict}

Failed Test Case:
{$failedTestCase}

Student Submission:
{$sourceCode}
PROMPT;

        $response = Http::timeout(120)
            ->post($ollamaUrl, [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Ollama API error: '.$response->status().' | '.$response->body()
            );
        }

        $data = $response->json();

        $text = $data['response'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        $feedback = json_decode(trim($text), true);

        if (! is_array($feedback)) {
            throw new RuntimeException(
                'Ollama returned invalid JSON: '.$text
            );
        }

        return [
            'available' => true,
            'provider' => 'Ollama',
            'model' => $model,
            'diagnosis' => $feedback['diagnosis'] ?? '',
            'hint_1' => $feedback['hint_1'] ?? '',
            'hint_2' => $feedback['hint_2'] ?? '',
            'hint_3' => $feedback['hint_3'] ?? '',
            'explanation' => $feedback['explanation'] ?? '',
        ];
    }
}