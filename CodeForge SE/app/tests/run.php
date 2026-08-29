<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/core/helpers.php';
require_once $root . '/services/CodeDnaCalculator.php';
require_once $root . '/services/GamificationCalculator.php';
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

check(GamificationCalculator::xpForDifficulty('Easy') === 10, 'Gamification Easy XP reward');
check(GamificationCalculator::xpForDifficulty('Hard') === 50, 'Gamification Hard XP reward');
check(GamificationCalculator::totalXp(['Easy','Medium','Hard']) === 85, 'Gamification XP total is deterministic');
$streak = GamificationCalculator::streakStats(['2026-08-27','2026-08-28','2026-08-29'], '2026-08-29');
check($streak['current_streak'] === 3 && $streak['longest_streak'] === 3, 'Gamification counts consecutive solve streaks');
$streak = GamificationCalculator::streakStats(['2026-08-20','2026-08-21','2026-08-25','2026-08-26'], '2026-08-26');
check($streak['current_streak'] === 2 && $streak['longest_streak'] === 2, 'Gamification handles streak gaps');
$streak = GamificationCalculator::streakStats(['2026-08-20','2026-08-21'], '2026-08-29');
check($streak['current_streak'] === 0 && $streak['longest_streak'] === 2, 'Stale streak displays as inactive without losing the record');
$level = GamificationCalculator::levelProgress(75, [
    ['level'=>1,'title'=>'A','xp_required'=>0],
    ['level'=>2,'title'=>'B','xp_required'=>50],
    ['level'=>3,'title'=>'C','xp_required'=>100],
]);
check((int)$level['current']['level'] === 2 && (int)$level['percent'] === 50, 'Level progress uses threshold range');

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

$schemaText = file_get_contents($root . '/database/schema_and_seed.sql');
$dashboardSource = file_get_contents($root . '/dashboard.php');
$profileSource = file_get_contents($root . '/profile.php');
$practiceSource = file_get_contents($root . '/services/ProblemPracticeService.php');
$ghostSource = file_get_contents($root . '/services/GhostRaceService.php');
$advisorSource = file_get_contents($root . '/services/QuestAdvisorService.php');
$universitySource = file_get_contents($root . '/university.php');
$guideSource = file_get_contents($root . '/how_it_works.php');

check(str_contains($schemaText, 'CREATE TABLE gamification_profiles') && str_contains($schemaText, 'CREATE TABLE user_badges'), 'Gamification schema is part of clean install');
check(str_contains($schemaText, "'first_steps'") && str_contains($schemaText, "'topic_master'"), 'Badge catalog is seeded');
check(str_contains($dashboardSource, 'Quest Advisor') && str_contains($dashboardSource, 'Skill Tree'), 'Dashboard includes teammate recommendation features');
check(str_contains($dashboardSource, 'GamificationService') && str_contains($profileSource, 'achievement-grid'), 'Progress and badge UI are integrated');
check(str_contains($profileSource, 'LIMIT 20') && str_contains($profileSource, 's.language'), 'Profile keeps extended submission history');
check(
    str_contains($profileSource, 'array_key_exists(\'solved_count\', $user)')
    && str_contains($profileSource, 'array_key_exists(\'submission_count\', $user)')
    && str_contains($profileSource, 'AS solved_count')
    && str_contains($profileSource, 'AS submission_count')
    && str_contains($profileSource, '($user[\'solved_count\'] ?? 0)')
    && str_contains($profileSource, '($user[\'submission_count\'] ?? 0)'),
    'Profile safely repairs missing submission counters without warnings'
);
check(str_contains($advisorSource, 'NOT EXISTS') && str_contains($advisorSource, 'SELECT DISTINCT problem_id'), 'Quest advisor avoids duplicate solve counting');
$commitPos = strpos($practiceSource, '$this->pdo->commit();');
$rewardPos = strpos($practiceSource, 'syncUserFromHistory');
check($commitPos !== false && $rewardPos !== false && $commitPos < $rewardPos, 'Gamification runs after the core submission commit');
$ghostCommitPos = strpos($ghostSource, '$this->pdo->commit();', strpos($ghostSource, 'public function submit'));
$ghostRewardPos = strpos($ghostSource, 'syncUserFromHistory', strpos($ghostSource, 'public function submit'));
check(
    $ghostCommitPos !== false && $ghostRewardPos !== false && $ghostCommitPos < $ghostRewardPos,
    'Ghost Race accepted solves synchronize gamification after the race commit'
);
check(str_contains($universitySource, 'ROW_NUMBER() OVER') && str_contains($universitySource, 'top_username'), 'University top solver is computed without N+1 queries');
check(file_exists($root . '/database/migrate_008_gamification.php'), 'Idempotent PATCH-008 migration exists');
check(str_contains($guideSource, 'Gamification') && str_contains($guideSource, 'Quest Advisor') && str_contains($guideSource, 'Skill Tree'), 'Technical guide documents merged features');

$progressSource = file_get_contents($root . '/progress.php');
$primarySidebarTargets = ['problems.php','contests.php','rivalry.php','university.php','database.php','code_dna.php','progress.php','ghost_race.php','sql_battle.php','search.php','profile.php','how_it_works.php'];
$sidebarComplete = true;
foreach ($primarySidebarTargets as $target) {
    if (!str_contains($headerText, 'href="' . $target)) { $sidebarComplete = false; break; }
}
check($sidebarComplete && str_contains($headerText, 'Gamification') && str_contains($headerText, 'Quest Advisor') && str_contains($headerText, 'Skill Tree'), 'Sidebar exposes every primary user-facing module and PATCH-008 feature');

$dashboardTargets = ['problems.php','contests.php','rivalry.php','university.php','code_dna.php','progress.php','ghost_race.php','sql_battle.php','search.php','database.php'];
$dashboardComplete = true;
foreach ($dashboardTargets as $target) {
    if (!str_contains($dashboardSource, 'href="' . $target)) { $dashboardComplete = false; break; }
}
check($dashboardComplete && str_contains($dashboardSource, 'Feature Launchpad'), 'Dashboard launchpad exposes the main working modules');
check(
    !str_contains($dashboardSource, 'href="profile.php')
    && !str_contains($dashboardSource, 'href="how_it_works.php"'),
    'Profile and How It Works stay out of the dashboard launchpad'
);
check(
    str_contains($headerText, 'class="profile-chip"')
    && str_contains($headerText, 'href="profile.php?id=')
    && str_contains($headerText, '>My Profile</a>')
    && str_contains($headerText, '>How It Works</a>'),
    'Profile remains available from the top-right user control and sidebar, while How It Works remains in the sidebar'
);
$footerSource = file_get_contents($root . '/includes/footer.php');
check(
    str_contains($footerSource, 'CodeForge · <?= e($pageTitle')
    && !str_contains($footerSource, 'Optimized Plain-PHP Build')
    && !str_contains($footerSource, 'PDO · MariaDB')
    && !str_contains($footerSource, 'No framework'),
    'Footer is reduced to CodeForge and the current page label'
);
check(
    str_contains($progressSource, 'GamificationService')
    && str_contains($progressSource, 'QuestAdvisorService')
    && str_contains($progressSource, 'Quest Advisor')
    && str_contains($progressSource, 'Skill Tree')
    && str_contains($progressSource, 'Badge cabinet')
    && str_contains($progressSource, 'id="gamification"')
    && str_contains($progressSource, 'id="quest-advisor"')
    && str_contains($progressSource, 'id="skill-tree"'),
    'Progress Hub exposes gamification, achievements, quests and full mastery'
);

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
