<?php

declare(strict_types=1);

/**
 * Safe local prototype judge.
 * It does NOT execute arbitrary source code. It performs deterministic
 * structural checks so the DBMS/software-lab prototype can record realistic
 * submission timelines without exposing the machine to arbitrary code execution.
 */
final class PrototypeJudgeService
{
    public function evaluate(string $sourceCode, string $language, string $difficulty = 'Easy'): array
    {
        $source = trim($sourceCode);
        $language = trim($language) !== '' ? trim($language) : 'C++';

        if (preg_match('/simulate\s*:\s*(wa|tle|ce|re)/i', $source, $match)) {
            $verdict = strtoupper($match[1]);
            return $this->result($verdict, $source, $difficulty);
        }

        if (strlen($source) < 35) {
            return $this->result('CE', $source, $difficulty);
        }

        $hasStructure = match (strtolower($language)) {
            'c++', 'cpp' => str_contains($source, 'main') && (str_contains($source, '#include') || str_contains($source, 'using namespace')),
            'python' => str_contains($source, 'print') || str_contains($source, 'def '),
            'javascript', 'js' => str_contains($source, 'console.log') || str_contains($source, 'function') || str_contains($source, '=>'),
            'php' => str_contains($source, '<?php') || str_contains($source, 'echo'),
            default => strlen($source) >= 80,
        };

        if (!$hasStructure || strlen($source) < 70) {
            return $this->result('WA', $source, $difficulty);
        }

        return $this->result('AC', $source, $difficulty);
    }

    private function result(string $verdict, string $source, string $difficulty): array
    {
        $base = abs((int) crc32($source ?: 'empty'));
        $difficultyFactor = match (strtolower($difficulty)) {
            'easy' => 5,
            'medium' => 15,
            'hard' => 30,
            default => 10,
        };

        return [
            'verdict' => $verdict,
            'runtime_ms' => 8 + ($base % 45) + $difficultyFactor,
            'memory_kb' => 900 + (strlen($source) * 2) + ($difficultyFactor * 20),
            'failed_test_case' => $verdict === 'AC' ? null : (($base % 8) + 1),
            'feedback' => match ($verdict) {
                'AC' => 'Accepted by the safe prototype judge.',
                'WA' => 'Wrong answer simulation: add a more complete solution structure or use simulate:wa for demos.',
                'TLE' => 'Time limit exceeded simulation.',
                'CE' => 'Compilation/structure check failed in the prototype judge.',
                'RE' => 'Runtime error simulation.',
                default => 'Submission evaluated by the prototype judge.',
            },
        ];
    }
}
