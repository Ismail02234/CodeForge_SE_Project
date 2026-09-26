<?php

namespace App\Services;

final class PrototypeJudgeService
{
    public function evaluate(
        string $sourceCode,
        string $language,
        string $difficulty = 'Easy',
        ?string $problemId = null
    ): array {
        $source = trim($sourceCode);
        $language = trim($language) ?: 'C++';

        if (preg_match('/simulate\s*:\s*(wa|tle|ce|re)/i', $source, $m)) {
            return $this->result(strtoupper($m[1]), $source, $difficulty);
        }

        if (strlen($source) < 35) {
            return $this->result('CE', $source, $difficulty, 'Submission is too incomplete to evaluate.');
        }

        if (! $this->looksLikeProgram($source, $language)) {
            return $this->result('CE', $source, $difficulty, 'Compilation/structure check failed in the prototype judge.');
        }

        if ($problemId !== null && trim($problemId) !== '') {
            [$matches, $feedback] = $this->matchesProblem(trim($problemId), $source);

            return $this->result($matches ? 'AC' : 'WA', $source, $difficulty, $feedback);
        }

        // Preserve the old generic behavior for callers such as Ghost Race that do not
        // supply a problem id. Normal problem practice now uses the stricter path above.
        return $this->result(strlen($source) < 70 ? 'WA' : 'AC', $source, $difficulty);
    }

    private function looksLikeProgram(string $source, string $language): bool
    {
        $lower = strtolower($source);

        return match (strtolower($language)) {
            'c++', 'cpp' => str_contains($lower, 'main')
                && (str_contains($lower, '#include') || str_contains($lower, 'using namespace')),
            'python', 'py' => str_contains($lower, 'print') || str_contains($lower, 'def '),
            'java' => str_contains($lower, 'static void main') && str_contains($lower, 'class '),
            'javascript', 'js' => str_contains($lower, 'console.log') || str_contains($lower, 'function') || str_contains($lower, '=>'),
            'php' => str_contains($lower, '<?php') || str_contains($lower, 'echo'),
            default => strlen($source) >= 80,
        };
    }

    private function matchesProblem(string $problemId, string $source): array
    {
        $s = strtolower($source);
        $hasInput = $this->containsAny($s, ['cin', 'scanf', 'input(', 'scanner', 'bufferedreader', 'readline', 'std::getline']);
        $hasOutput = $this->containsAny($s, ['cout', 'printf', 'print(', 'system.out.print', 'console.log', 'echo ']);
        $hasLoop = preg_match('/\b(for|while)\s*\(|\bfor\s+\w+\s+in\b/i', $source) === 1;

        $ok = match (strtolower($problemId)) {
            'p1' => $hasInput && $hasOutput && $this->containsAny($s, ['hello', 'hi ', 'welcome', 'greet']),
            'p2' => $this->matchesEvenOdd($source, $s, $hasInput, $hasOutput),
            'p3' => $hasInput && $hasOutput && $hasLoop
                && $this->containsAny($s, ['+=', 'accumulate(', 'sum('])
                && ! str_contains($s, 'sum -='),
            'p4' => $hasInput && $hasOutput
                && $this->containsAny($s, ['lca', 'binary lifting', 'ancestor', 'up[', 'up ['])
                && $this->containsAny($s, ['depth', 'parent'])
                && $hasLoop,
            'p5' => $hasInput && $hasOutput
                && $this->containsAny($s, ['hash', 'rolling'])
                && $this->containsAny($s, ['prefix', 'pref', 'power', 'pow'])
                && $this->containsAny($s, ['mod', '%']),
            'p6' => $hasInput && $hasOutput
                && $this->containsAny($s, ['priority_queue', 'heapq', 'priorityqueue'])
                && $this->containsAny($s, ['dist', 'distance'])
                && $this->containsAny($s, ['weight', ' w', 'edge']),
            'p7' => $hasInput && $hasOutput && $hasLoop
                && $this->containsAny($s, ['dp[', 'dp [', 'vector<int> dp', 'vector<long long> dp', 'dp ='])
                && $this->containsAny($s, ['max(', 'std::max', 'math.max']),
            'p8' => $hasInput && $hasOutput
                && (str_contains($s, 'lower_bound')
                    || ($this->containsAny($s, ['mid', 'middle'])
                        && $this->containsAny($s, ['left', ' lo', 'low'])
                        && $this->containsAny($s, ['right', ' hi', 'high'])
                        && str_contains($s, '>='))),
            'p9' => $hasInput && $hasOutput
                && $this->containsAny($s, ['sort(', 'std::sort', 'arrays.sort', '.sort('])
                && $this->containsAny($s, ['merge', 'current', 'end', 'second']),
            'p10' => $hasInput && $hasOutput && $hasLoop
                && $this->containsAny($s, ['prefix', 'pref'])
                && $this->containsAny($s, ['freq', 'count']),
            'p11' => $hasInput && $hasOutput
                && $this->containsAny($s, ['tolower', '.lower(', 'lowercase'])
                && ($this->containsAny($s, ['reverse(', 'reversed('])
                    || ($this->containsAny($s, ['left', ' l']) && $this->containsAny($s, ['right', ' r']))),
            'p12' => $hasInput && $hasOutput
                && $this->containsAny($s, ['bfs', 'dfs'])
                && $this->containsAny($s, ['diameter', 'farthest', 'dist']),
            'p13' => $hasInput && $hasOutput && $hasLoop
                && str_contains($s, '%')
                && $this->containsAny($s, ['>>= 1', '>>=1', '/= 2', '/=2', 'b /= 2', 'b>>=1', 'exponent'])
                && $this->containsAny($s, ['*', 'multiply']),
            'p14' => $hasInput && $hasOutput
                && $this->containsAny($s, ['indegree', 'in_degree', 'topo', 'topological'])
                && $this->containsAny($s, ['dp[', 'dp [', 'paths', 'ways'])
                && $this->containsAny($s, ['queue', 'deque']),
            default => $hasInput && $hasOutput && $hasLoop && strlen($source) >= 120,
        };

        return [
            $ok,
            $ok
                ? 'Accepted by the strict problem-aware prototype judge.'
                : 'The submission does not demonstrate the core logic expected for this problem. Review the task requirements and algorithm before resubmitting.',
        ];
    }

    private function matchesEvenOdd(string $source, string $lower, bool $hasInput, bool $hasOutput): bool
    {
        if (! $hasInput || ! $hasOutput) {
            return false;
        }

        $hasParityCheck = preg_match('/%\s*2|&\s*1/', $source) === 1;
        if (! $hasParityCheck || ! str_contains($lower, 'even') || ! str_contains($lower, 'odd')) {
            return false;
        }

        // Catch the common intentionally-wrong inversion used when testing the judge.
        $evenCondition = '(?:%\s*2\s*==\s*0|%\s*2\s*!=\s*1|&\s*1\s*\)?\s*==\s*0)';
        if (preg_match('/'.$evenCondition.'.{0,180}?["\']odd["\'].{0,180}?\belse\b/is', $source) === 1) {
            return false;
        }

        $oddCondition = '(?:%\s*2\s*!=\s*0|%\s*2\s*==\s*1|&\s*1\s*\)?\s*==\s*1)';
        if (preg_match('/'.$oddCondition.'.{0,180}?["\']even["\'].{0,180}?\belse\b/is', $source) === 1) {
            return false;
        }

        return true;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function result(string $verdict, string $source, string $difficulty, ?string $feedback = null): array
    {
        $base = abs((int) crc32($source ?: 'empty'));
        $factor = match (strtolower($difficulty)) {
            'easy' => 5,
            'medium' => 15,
            'hard' => 30,
            default => 10,
        };

        return [
            'verdict' => $verdict,
            'runtime_ms' => 8 + ($base % 45) + $factor,
            'memory_kb' => 900 + (strlen($source) * 2) + ($factor * 20),
            'failed_test_case' => $verdict === 'AC' ? null : (($base % 8) + 1),
            'feedback' => $feedback ?? match ($verdict) {
                'AC' => 'Accepted by the safe prototype judge.',
                'WA' => 'Wrong answer simulation: add a more complete solution structure.',
                'TLE' => 'Time limit exceeded simulation.',
                'CE' => 'Compilation/structure check failed in the prototype judge.',
                'RE' => 'Runtime error simulation.',
                default => 'Submission evaluated by the prototype judge.',
            },
        ];
    }
}