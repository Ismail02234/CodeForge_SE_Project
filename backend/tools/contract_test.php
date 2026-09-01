<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$passed = 0;
$failed = 0;

function check(bool $condition, string $name): void
{
    global $passed, $failed;
    if ($condition) {
        echo "[OK]   {$name}\n";
        $passed++;
    } else {
        echo "[FAIL] {$name}\n";
        $failed++;
    }
}

$api = file_get_contents($root.'/routes/api.php');
$web = file_get_contents($root.'/routes/web.php');
$bootstrap = file_get_contents($root.'/bootstrap/app.php');
$problemController = file_get_contents($root.'/app/Http/Controllers/ProblemController.php');
$migration = file_get_contents($root.'/database/migrations/2026_09_01_000100_adopt_or_create_codeforge_schema.php');

foreach ([
    '/dashboard', '/problems', '/performance-profile', '/profiles/{id}', '/rivalry', '/universities', '/search',
    '/contests', '/ghost-races/options', '/sql/challenges', '/database/users', '/sql-lab',
] as $route) {
    check(str_contains($api, $route), "API route {$route}");
}

foreach (['/login', '/register', '/logout'] as $route) {
    check(str_contains($web, $route), "web auth route {$route}");
}

check(str_contains($bootstrap, 'statefulApi()'), 'Sanctum stateful API middleware');
check(str_contains($bootstrap, "'admin' => EnsureAdmin::class"), 'admin middleware alias');
check(
    (bool) preg_match(
        '~submit\s*\(\s*\$request->user\(\)->id\s*,\s*\$session\[\'id\'\]\s*,\s*\$id~',
        $problemController
    ),
    'problem submit uses session id before problem id'
);

foreach ([
    'users', 'universities', 'problems', 'submissions', 'problem_sessions', 'contests',
    'ghost_races', 'sql_challenges', 'sql_battles', 'sql_attempts', 'activity_logs',
    'arena_users', 'arena_problems', 'arena_submissions', 'topicstats',
] as $table) {
    check(str_contains($migration, "hasTable('{$table}')"), "schema covers {$table}");
}

require_once $root.'/app/Services/PerformanceProfileCalculator.php';
require_once $root.'/app/Services/PrototypeJudgeService.php';
require_once $root.'/app/Services/SqlJudgeService.php';

use App\Services\PerformanceProfileCalculator;
use App\Services\PrototypeJudgeService;
use App\Services\SqlJudgeService;

check(PerformanceProfileCalculator::speedScore('Easy', 180) === 82, 'Performance Profile speed formula');
check(PerformanceProfileCalculator::difficultyScore('Hard') === 100, 'Performance Profile hard difficulty score');
check(PerformanceProfileCalculator::clamp(120) === 100, 'Performance Profile clamp upper bound');
check(PerformanceProfileCalculator::archetype(['accuracy' => 90, 'speed' => 60, 'challenge' => 60, 'versatility' => 50, 'consistency' => 70])['name'] === 'Precision Solver', 'Performance Profile archetype');

$judge = new PrototypeJudgeService;
$accepted = $judge->evaluate("#include <bits/stdc++.h>\nint main(){ return 0; }", 'C++', 'Easy');
check(in_array($accepted['verdict'], ['AC', 'WA', 'TLE', 'CE'], true), 'prototype judge returns known verdict');
check(isset($accepted['runtime_ms'], $accepted['memory_kb']), 'prototype judge returns performance metadata');

$ref = new ReflectionClass(SqlJudgeService::class);
$sql = $ref->newInstanceWithoutConstructor();
[$ok] = $sql->validate('SELECT username FROM arena_users');
check($ok, 'SQL sandbox allows arena SELECT');
[$ok] = $sql->validate('DROP TABLE arena_users');
check(! $ok, 'SQL sandbox blocks destructive SQL');
[$ok] = $sql->validate('SELECT * FROM users');
check(! $ok, 'SQL sandbox blocks production users table');
[$ok] = $sql->validate('SELECT * FROM arena_users; SELECT * FROM arena_problems');
check(! $ok, 'SQL sandbox blocks multiple statements');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
