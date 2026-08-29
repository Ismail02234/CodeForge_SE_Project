<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/CodeDnaService.php';

$user = require_login($pdo);
$dna = (new CodeDnaService($pdo))->calculate((string) $user['id']);

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

<div class="grid grid-3 mt-3">
    <a class="card card-pad feature-card" href="code_dna.php"><div class="feature-icon"><i class="bi bi-hexagon"></i></div><h3>Code DNA</h3><p>Data-driven skill fingerprint across accuracy, speed, challenge handling, consistency, and topic mastery.</p><span class="text-cyan">Open intelligence →</span></a>
    <a class="card card-pad feature-card" href="ghost_race.php"><div class="feature-icon"><i class="bi bi-ghost"></i></div><h3>Ghost Race</h3><p>Race against the recorded solving timeline of another programmer without requiring them online.</p><span class="text-cyan">Choose a ghost →</span></a>
    <a class="card card-pad feature-card" href="sql_battle.php"><div class="feature-icon"><i class="bi bi-database-gear"></i></div><h3>SQL Battle Arena</h3><p>Compete on safe SELECT-only challenges scored for correctness, speed, and estimated efficiency.</p><span class="text-cyan">Enter arena →</span></a>
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
