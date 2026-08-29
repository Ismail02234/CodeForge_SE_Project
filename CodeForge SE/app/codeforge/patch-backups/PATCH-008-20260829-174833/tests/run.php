<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/core/helpers.php';
require_once $root . '/services/CodeDnaCalculator.php';
require_once $root . '/services/PrototypeJudgeService.php';
require_once $root . '/services/SqlJudgeService.php';

$passed = 0; $failed = 0;
function check(bool $condition, string $name): void {
    global $passed, $failed;
    if ($condition) { echo "[PASS] {$name}\n"; $passed++; }
    else { echo "[FAIL] {$name}\n"; $failed++; }
}

check(CodeDnaCalculator::clamp(120) === 100, 'DNA clamp upper bound');
check(CodeDnaCalculator::clamp(-5) === 0, 'DNA clamp lower bound');
check(CodeDnaCalculator::difficultyScore('Hard') > CodeDnaCalculator::difficultyScore('Easy'), 'Hard difficulty scores higher');
check(CodeDnaCalculator::topicScore(100,100,100,100) === 100, 'Topic score max');
check(CodeDnaCalculator::consistency([1,1,1,1]) === 100, 'Consistency stable accepted streak');
check(CodeDnaCalculator::archetype(['speed'=>90,'accuracy'=>80])['name'] === 'Fast Strategist', 'Archetype classification');

$judge = new PrototypeJudgeService();
$r = $judge->evaluate('short', 'C++', 'Easy');
check($r['verdict'] === 'CE', 'Prototype judge rejects tiny code');
$r = $judge->evaluate("// simulate:wa\n" . str_repeat('x',80), 'C++', 'Easy');
check($r['verdict'] === 'WA', 'Prototype judge deterministic WA override');
$r = $judge->evaluate("#include <bits/stdc++.h>\nusing namespace std;\nint main(){ cout << 1 << endl; return 0; }", 'C++', 'Easy');
check($r['verdict'] === 'AC', 'Prototype judge accepts structured code');
check(isset($r['feedback']), 'Prototype judge returns feedback');

$reflection = new ReflectionClass(SqlJudgeService::class);
$sqlJudge = $reflection->newInstanceWithoutConstructor();
[$ok] = $sqlJudge->validate('SELECT username FROM arena_users');
check($ok === true, 'SQL validator allows SELECT from sandbox');
[$ok] = $sqlJudge->validate('DROP TABLE arena_users');
check($ok === false, 'SQL validator rejects DROP');
[$ok] = $sqlJudge->validate('SELECT * FROM users');
check($ok === false, 'SQL validator blocks production users table');
[$ok] = $sqlJudge->validate('SELECT * FROM arena_users; DELETE FROM arena_users');
check($ok === false, 'SQL validator rejects multiple statements');
[$ok] = $sqlJudge->validate('WITH x AS (SELECT username FROM arena_users) SELECT * FROM x');
check($ok === true, 'SQL validator allows non-recursive sandbox CTE');
[$ok] = $sqlJudge->validate('WITH RECURSIVE x AS (SELECT 1 FROM arena_users) SELECT * FROM x');
check($ok === false, 'SQL validator rejects recursive CTE');
[$ok] = $sqlJudge->validate('SELECT CURRENT_USER() FROM arena_users');
check($ok === false, 'SQL validator blocks server identity functions');
[$ok] = $sqlJudge->validate('SELECT @x := rating FROM arena_users');
check($ok === false, 'SQL validator blocks SQL variables');
check(str_contains(asset_url('assets/css/app.css'), '?v='), 'Asset URLs include cache-busting version');

$hash = null;
foreach (file($root . '/database/schema_and_seed.sql') as $line) {
    if (str_contains($line, "('u1','Ismail'")) {
        preg_match("/Ismail','([^']+)'/", $line, $m); $hash = $m[1] ?? null; break;
    }
}
check(is_string($hash) && password_verify('123456', $hash), 'Seeded Ismail password is valid');

$adminHash = null;
foreach (file($root . '/database/schema_and_seed.sql') as $line) {
    if (str_contains($line, "('u_admin','Admin'")) {
        preg_match("/Admin','([^']+)'/", $line, $m); $adminHash = $m[1] ?? null; break;
    }
}
check(is_string($adminHash) && password_verify('admin123', $adminHash), 'Seeded Admin password is valid');

$missing = [];
$phpFiles = glob($root . '/*.php');
foreach ($phpFiles as $file) {
    $text = file_get_contents($file);
    preg_match_all('/(?:href|action)=["\']([^"\']+\.php)(?:\?[^"\']*)?["\']/i', $text, $matches);
    foreach ($matches[1] as $target) {
        if (str_starts_with($target, 'http')) continue;
        $target = basename($target);
        if (!file_exists($root . '/' . $target)) $missing[$target] = true;
    }
}
check($missing === [], 'All statically-linked top-level PHP pages exist' . ($missing ? ': '.implode(',',array_keys($missing)) : ''));

$indexText = file_get_contents($root . '/index.php');
$dashboardText = file_get_contents($root . '/dashboard.php');
$headerText = file_get_contents($root . '/includes/header.php');
$aggressiveCss = file_get_contents($root . '/assets/css/aggressive.css');
$polishCss = file_get_contents($root . '/assets/css/polish.css');

check(str_contains($indexText, 'class="forge-landing"') && !str_contains($indexText, 'require_login('), 'index.php remains the dedicated public landing page');
check(str_contains($dashboardText, 'require_login('), 'dashboard.php remains authenticated');
check(str_contains($headerText, 'topbar-logout') && str_contains($indexText, 'logout.php'), 'Logout controls exist in app and landing page');
check(str_contains($headerText, 'Space+Grotesk') && str_contains($polishCss, 'Space Grotesk'), 'Futuristic readable font system is installed');
check(str_contains($aggressiveCss, 'overscroll-behavior-y: contain') && str_contains($aggressiveCss, 'overflow-y: auto'), 'Sidebar keeps independent scrolling');

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
