<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/services/CodeDnaService.php';
require_once __DIR__ . '/services/GamificationService.php';

$me = require_login($pdo);
$id = (string) ($_GET['id'] ?? $me['id']);
$user = (new UserRepository($pdo))->find($id);

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

$dna = (new CodeDnaService($pdo))->calculate($id);
$gamification = (new GamificationService($pdo))->summary($id);

$recentStmt = $pdo->prepare(
    "SELECT p.title, p.difficulty, s.verdict, s.language, s.submitted_at
     FROM submissions s
     INNER JOIN problems p ON p.id = s.problem_id
     WHERE s.user_id = :uid
     ORDER BY s.submitted_at DESC, s.id DESC
     LIMIT 20"
);
$recentStmt->execute(['uid' => $id]);
$recent = $recentStmt->fetchAll();

$pageTitle = $user['username'];
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-grid">
        <div>
            <div class="eyebrow">Coder Profile</div>
            <h1><?= e($user['username']) ?></h1>
            <p><?= e($user['university'] ?? 'Independent') ?> · <?= e($user['rank']) ?></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="code_dna.php?user=<?= e($user['id']) ?>"><i class="bi bi-hexagon"></i>Inspect Code DNA</a>
                <a class="btn btn-secondary" href="rivalry.php?a=<?= e($me['id']) ?>&b=<?= e($user['id']) ?>"><i class="bi bi-lightning-charge"></i>Compare</a>
            </div>
        </div>
        <div class="grid grid-2">
            <div class="card stat"><div class="label">Rating</div><div class="value cyan"><?= (int) $user['rating'] ?></div></div>
            <div class="card stat"><div class="label">DNA</div><div class="value purple"><?= (int) $dna['overall'] ?>%</div></div>
            <div class="card stat"><div class="label">Solved</div><div class="value green"><?= (int) $user['solved_count'] ?></div></div>
            <div class="card stat"><div class="label">Submissions</div><div class="value"><?= (int) $user['submission_count'] ?></div></div>
        </div>
    </div>
</section>

<?php if(!empty($gamification['enabled'])): ?>
<section class="grid grid-2 mb-3">
    <div class="card card-pad progress-card">
        <div class="card-head">
            <div><span class="eyebrow">Progression</span><h2><?= e($gamification['level']['title']) ?></h2></div>
            <span class="pill pill-neutral">LEVEL <?= (int)$gamification['level']['level'] ?></span>
        </div>
        <div class="metric-row">
            <label>XP</label>
            <div class="progress"><span style="width:<?= (int)$gamification['level_progress'] ?>%"></span></div>
            <b><?= (int)$gamification['xp'] ?></b>
        </div>
        <div class="mini-stat-grid mt-2">
            <div><span>Current streak</span><strong><?= (int)$gamification['current_streak'] ?> days</strong></div>
            <div><span>Longest streak</span><strong><?= (int)$gamification['longest_streak'] ?> days</strong></div>
            <div><span>Earned badges</span><strong><?= (int)$gamification['earned_badge_count'] ?>/<?= count($gamification['badges']) ?></strong></div>
        </div>
        <?php if($gamification['next_level']): ?>
            <p class="muted small mt-2">Next: <?= e($gamification['next_level']['title']) ?> at <?= (int)$gamification['next_level']['xp_required'] ?> XP.</p>
        <?php else: ?>
            <p class="text-green small mt-2">Maximum progression level reached.</p>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-head"><div><span class="eyebrow">Achievements</span><h2>Badge cabinet</h2></div><span class="pill pill-neutral"><?= (int)$gamification['earned_badge_count'] ?> EARNED</span></div>
        <div class="achievement-grid">
            <?php foreach($gamification['badges'] as $badge): $earned = !empty($badge['earned_at']); ?>
                <div class="achievement-item <?= $earned ? 'is-earned' : 'is-locked' ?>" title="<?= e($badge['description']) ?>">
                    <i class="bi <?= e($badge['icon']) ?>"></i>
                    <div><strong><?= e($badge['name']) ?></strong><small><?= e($earned ? 'Unlocked' : 'Locked') ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="card table-wrap">
    <div class="card-head"><h3>Recent submission history</h3><span class="pill pill-neutral"><?= count($recent) ?> RECENT</span></div>
    <table class="data-table">
        <thead><tr><th>Problem</th><th>Difficulty</th><th>Verdict</th><th>Language</th><th>Submitted</th></tr></thead>
        <tbody>
        <?php foreach($recent as $row): ?>
            <tr>
                <td><?= e($row['title']) ?></td>
                <td><span class="pill <?= difficulty_class($row['difficulty']) ?>"><?= e($row['difficulty']) ?></span></td>
                <td class="<?= verdict_class($row['verdict']) ?>"><?= e($row['verdict']) ?></td>
                <td class="mono small"><?= e($row['language']) ?></td>
                <td><?= e($row['submitted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if(!$recent): ?><div class="empty compact">No submissions yet.</div><?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
