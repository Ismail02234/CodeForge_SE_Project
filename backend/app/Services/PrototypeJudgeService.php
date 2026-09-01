<?php

namespace App\Services;

final class PrototypeJudgeService
{
    public function evaluate(string $sourceCode, string $language, string $difficulty = 'Easy'): array
    {
        $source = trim($sourceCode);
        $language = trim($language) ?: 'C++';
        if (preg_match('/simulate\s*:\s*(wa|tle|ce|re)/i', $source, $m)) {
            return $this->result(strtoupper($m[1]), $source, $difficulty);
        }
        if (strlen($source) < 35) {
            return $this->result('CE', $source, $difficulty);
        }
        $ok = match (strtolower($language)) {
            'c++','cpp' => str_contains($source, 'main') && (str_contains($source, '#include') || str_contains($source, 'using namespace')),
            'python' => str_contains($source, 'print') || str_contains($source, 'def '),
            'javascript','js' => str_contains($source, 'console.log') || str_contains($source, 'function') || str_contains($source, '=>'),
            'php' => str_contains($source, '<?php') || str_contains($source, 'echo'),
            default => strlen($source) >= 80,
        };

        return $this->result((! $ok || strlen($source) < 70) ? 'WA' : 'AC', $source, $difficulty);
    }

    private function result(string $verdict, string $source, string $difficulty): array
    {
        $base = abs((int) crc32($source ?: 'empty'));
        $factor = match (strtolower($difficulty)) {
            'easy' => 5,'medium' => 15,'hard' => 30,default => 10
        };

        return [
            'verdict' => $verdict, 'runtime_ms' => 8 + ($base % 45) + $factor,
            'memory_kb' => 900 + (strlen($source) * 2) + ($factor * 20),
            'failed_test_case' => $verdict === 'AC' ? null : (($base % 8) + 1),
            'feedback' => match ($verdict) {
                'AC' => 'Accepted by the safe prototype judge.',
                'WA' => 'Wrong answer simulation: add a more complete solution structure.',
                'TLE' => 'Time limit exceeded simulation.',
                'CE' => 'Compilation/structure check failed in the prototype judge.',
                'RE' => 'Runtime error simulation.',
                default => 'Submission evaluated by the prototype judge.'
            },
        ];
    }
}
