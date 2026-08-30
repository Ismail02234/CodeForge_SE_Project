<?php
function mockJudge(string $code, array $problem): array {
    $difficulty = $problem['difficulty'] ?? 'Easy';
    $difficultyFailRate = [
        'Easy' => 0.15,
        'Medium' => 0.35,
        'Hard' => 0.55,
    ];

    $failRate = $difficultyFailRate[$difficulty] ?? 0.15;

    if (strlen($code) < 10) {
        return [
            'verdict' => 'WA',
            'runtime' => rand(20, 200),
            'memory' => rand(1500, 5000),
            'failedTestCase' => rand(1, 5),
        ];
    }

    $verdict = (mt_rand() / mt_getrandmax()) > $failRate ? 'AC' : 'WA';

    return [
        'verdict' => $verdict,
        'runtime' => rand(20, 800),
        'memory' => rand(1500, 15000),
        'failedTestCase' => $verdict === 'AC' ? null : rand(1, 5),
    ];
}
