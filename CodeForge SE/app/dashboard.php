<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/CodeDnaService.php';
require_once __DIR__ . '/services/GamificationService.php';
require_once __DIR__ . '/services/QuestAdvisorService.php';

$user = require_login($pdo);
$dna = (new CodeDnaService($pdo))->calculate((string) $user['id']);
$gamification = (new GamificationService($pdo))->summary((string) $user['id']);
$advisor = (new QuestAdvisorService($pdo))->dashboardData((string) $user['id']);

$solved = (int) $dna['stats']['solved_problems'];
$subs = (int) $dna['stats']['total_submissions'];

$winsStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM ghost_races
     WHERE challenger_id = :uid AND result = 'won'"
);
$winsStmt->execute(['uid' => $user['id']]);
$wins = (int) $winsStmt->fetchColumn();

$recentStmt = $pdo->prepare(
    'SELECT s.verdict, s.submitted_at, p.title, p.difficulty
     FROM submissions s
     INNER JOIN problems p ON p.id = s.problem_id
     WHERE s.user_id = :uid
     ORDER BY s.submitted_at DESC, s.id DESC
     LIMIT 6'
);
$recentStmt->execute(['uid' => $user['id']]);
$recent = $recentStmt->fetchAll();

$weakest = null;
if (!empty($dna['topics'])) {
    $tmp = $dna['topics'];
    uasort($tmp, static fn (array $a, array $b): int => $a['score'] <=> $b['score']);
    $weakest = array_key_first($tmp);
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-grid">
        <div>
            <div class="eyebrow">Command Center</div>
            <h1>Welcome back, <?= e($user['username']) ?>.</h1>
            <p>Your competitive-programming profile is live. Track performance, train weak areas, race historical solvers, and sharpen SQL skills from one workspace.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="problems.php"><i class="bi bi-play-fill"></i>Start practice</a>
                <a class="btn btn-secondary" href="code_dna.php"><i class="bi bi-hexagon"></i>View Code DNA</a>
                <a class="btn btn-secondary" href="progress.php"><i class="bi bi-stars"></i>Progress Hub</a>
            </div>
        </div>
        <div class="archetype">
            <div class="eyebrow">Current archetype</div>
            <h2><?= e($dna['archetype']['name']) ?></h2>
            <p><?= e($dna['archetype']['tagline']) ?></p>
            <div class="metric-row">
                <label>DNA score</label>
                <div class="progress"><span style="width:<?= (int) $dna['overall'] ?>%"></span></div>
                <b><?= (int) $dna['overall'] ?>%</b>
            </div>
        </div>
    </div>
</section>

<div class="grid grid-4">
    <div class="card stat"><div class="label">Rating</div><div class="value cyan"><?= (int) $user['rating'] ?></div><div class="delta"><?= e($user['rank']) ?></div></div>
    <div class="card stat"><div class="label">Problems solved</div><div class="value green"><?= $solved ?></div><div class="delta"><?= $subs ?> total submissions</div></div>
    <div class="card stat"><div class="label">Accuracy</div><div class="value amber"><?= (int) $dna['dimensions']['accuracy'] ?>%</div><div class="delta">Accepted / total attempts</div></div>
    <div class="card stat"><div class="label">Ghost wins</div><div class="value purple"><?= $wins ?></div><div class="delta">Historical race victories</div></div>
</div>

<section class="mt-3">
    <div class="card-head">
        <div><span class="eyebrow">Feature Launchpad</span><h2>Everything in CodeForge</h2></div>
        <span class="pill pill-neutral">ALL MODULES</span>
    </div>
    <div class="grid grid-4">
        <a class="card card-pad feature-card" href="problems.php"><div class="feature-icon"><i class="bi bi-braces"></i></div><h3>Problems</h3><p>Practice across topics and difficulties while building your submission history.</p><span class="text-cyan">Start solving →</span></a>
        <a class="card card-pad feature-card" href="contests.php"><div class="feature-icon"><i class="bi bi-trophy"></i></div><h3>Contests</h3><p>Join contest sets, track participation and compare leaderboard performance.</p><span class="text-cyan">Browse contests →</span></a>
        <a class="card card-pad feature-card" href="rivalry.php"><div class="feature-icon"><i class="bi bi-lightning-charge"></i></div><h3>Rivalry</h3><p>Compare two coders across rating, solves and competitive performance.</p><span class="text-cyan">Start comparison →</span></a>
        <a class="card card-pad feature-card" href="university.php"><div class="feature-icon"><i class="bi bi-mortarboard"></i></div><h3>Universities</h3><p>Explore institutional rankings, top solvers and university comparisons.</p><span class="text-cyan">View analytics →</span></a>

        <a class="card card-pad feature-card" href="code_dna.php"><div class="feature-icon"><i class="bi bi-hexagon"></i></div><h3>Code DNA</h3><p>Inspect your skill fingerprint across accuracy, speed, consistency and mastery.</p><span class="text-cyan">Open intelligence →</span></a>
        <a class="card card-pad feature-card" href="progress.php"><div class="feature-icon"><i class="bi bi-stars"></i></div><h3>Progress Hub</h3><p>See XP, levels, streaks, badges, Quest Advisor and your full Skill Tree.</p><span class="text-cyan">Track progress →</span></a>
        <a class="card card-pad feature-card" href="ghost_race.php"><div class="feature-icon"><i class="bi bi-ghost"></i></div><h3>Ghost Race</h3><p>Race against the recorded solving timeline of another programmer.</p><span class="text-cyan">Choose a ghost →</span></a>
        <a class="card card-pad feature-card" href="sql_battle.php"><div class="feature-icon"><i class="bi bi-database-gear"></i></div><h3>SQL Battle</h3><p>Solve safe SELECT-only SQL challenges scored for correctness and efficiency.</p><span class="text-cyan">Enter arena →</span></a>

        <a class="card card-pad feature-card" href="search.php"><div class="feature-icon"><i class="bi bi-search"></i></div><h3>Global Search</h3><p>Find coders, programming problems and universities from one place.</p><span class="text-cyan">Search CodeForge →</span></a>
        <a class="card card-pad feature-card" href="database.php"><div class="feature-icon"><i class="bi bi-table"></i></div><h3>Database Explorer</h3><p>Browse live user and relational data; editing remains restricted to admins.</p><span class="text-cyan">Open explorer →</span></a>

        <?php if ($user['role'] === 'admin'): ?>
        <a class="card card-pad feature-card" href="sql_lab.php"><div class="feature-icon"><i class="bi bi-terminal"></i></div><h3>SQL Lab</h3><p>Run protected read-only administrative SQL for inspection and debugging.</p><span class="text-cyan">Open SQL Lab →</span></a>
        <?php endif; ?>
    </div>
</section>

<div class="grid grid-3 mt-3">
    <div class="card card-pad progress-card">
        <div class="card-head"><h3><i class="bi bi-stars text-amber"></i> Progress</h3><span class="pill pill-neutral">LEVEL <?= (int)($gamification['level']['level'] ?? 1) ?></span></div>
        <?php if(!empty($gamification['enabled'])): ?>
            <div class="level-title"><?= e($gamification['level']['title'] ?? 'Code Sprout') ?></div>
            <div class="metric-row">
                <label>XP</label><div class="progress"><span style="width:<?= (int)$gamification['level_progress'] ?>%"></span></div><b><?= (int)$gamification['xp'] ?></b>
            </div>
            <div class="mini-stat-grid">
                <div><span>Current streak</span><strong><?= (int)$gamification['current_streak'] ?>d</strong></div>
                <div><span>Best streak</span><strong><?= (int)$gamification['longest_streak'] ?>d</strong></div>
                <div><span>Badges</span><strong><?= (int)$gamification['earned_badge_count'] ?></strong></div>
            </div>
            <?php if(!empty($gamification['earned_badges'])): ?><div class="reward-badges mt-2"><?php foreach(array_slice($gamification['earned_badges'],0,4) as $badge): ?><span class="mini-achievement" title="<?= e($badge['description']) ?>"><i class="bi <?= e($badge['icon']) ?>"></i><?= e($badge['name']) ?></span><?php endforeach; ?></div><?php endif; ?>
        <?php else: ?>
            <p class="muted small">Gamification data becomes available after the PATCH-008 database migration.</p>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-head"><h3><i class="bi bi-compass text-cyan"></i> Quest Advisor</h3><span class="pill pill-neutral">PERSONALIZED</span></div>
        <?php if(!empty($advisor['weakest'])): ?>
            <p class="muted small">Lowest completion area: <strong class="text-cyan"><?= e($advisor['weakest']['topic']) ?></strong> · <?= (int)$advisor['weakest']['solved_count'] ?>/<?= (int)$advisor['weakest']['total_problems'] ?> solved.</p>
            <div class="list">
                <?php foreach($advisor['recommendations'] as $rec): ?>
                    <a class="list-item" href="solve.php?id=<?= e($rec['id']) ?>"><div><strong><?= e($rec['title']) ?></strong><br><small><?= e($rec['topic']) ?></small></div><span class="pill <?= difficulty_class($rec['difficulty']) ?>"><?= e($rec['difficulty']) ?></span></a>
                <?php endforeach; ?>
                <?php if(!$advisor['recommendations']): ?><div class="empty compact">This topic is already complete.</div><?php endif; ?>
            </div>
        <?php else: ?><div class="empty compact">No problem topics are available yet.</div><?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-head"><h3><i class="bi bi-diagram-3 text-green"></i> Skill Tree</h3><span class="pill pill-neutral"><?= count($advisor['mastery']) ?> TOPICS</span></div>
        <div class="skill-tree-list">
            <?php foreach(array_slice($advisor['mastery'],0,6) as $topicRow): ?>
                <div class="topic-bar">
                    <span class="name"><?= e($topicRow['topic']) ?></span>
                    <div class="progress"><span style="width:<?= (int)$topicRow['mastery_percent'] ?>%"></span></div>
                    <b><?= (int)$topicRow['mastery_percent'] ?>%</b>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid grid-2 mt-3">
    <div class="card card-pad">
        <div class="card-head"><h3>DNA snapshot</h3><a class="text-cyan" href="code_dna.php">Details</a></div>
        <?php foreach ($dna['dimensions'] as $key => $value): ?>
            <div class="metric-row">
                <label><?= e($dna['dimension_labels'][$key] ?? ucfirst(str_replace('_', ' ', $key))) ?></label>
                <div class="progress"><span style="width:<?= (int) $value ?>%"></span></div>
                <b><?= (int) $value ?>%</b>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card card-pad">
        <div class="card-head"><h3>Recent activity</h3><span class="badge-dot text-green">Live DB</span></div>
        <div class="list">
            <?php foreach ($recent as $row): ?>
                <div class="list-item">
                    <div><strong><?= e($row['title']) ?></strong><br><small><?= e($row['submitted_at']) ?></small></div>
                    <div class="<?= verdict_class($row['verdict']) ?> mono"><?= e($row['verdict']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if ($recent === []): ?><div class="empty compact">No submissions yet.</div><?php endif; ?>
        </div>
        <?php if ($weakest): ?><div class="callout mt-2">Suggested focus: <strong><?= e($weakest) ?></strong> is currently your lowest-scoring tracked topic.</div><?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
