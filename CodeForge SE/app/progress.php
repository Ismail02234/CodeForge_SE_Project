<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/GamificationService.php';
require_once __DIR__ . '/services/QuestAdvisorService.php';

$user = require_login($pdo);
$gamification = (new GamificationService($pdo))->summary((string) $user['id']);
$advisor = (new QuestAdvisorService($pdo))->dashboardData((string) $user['id'], 6);

$pageTitle = 'Progress Hub';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <div class="eyebrow">Progress Intelligence</div>
        <h1>Progress Hub</h1>
        <p>One place for XP, levels, streaks, achievements, Quest Advisor recommendations and your complete topic Skill Tree.</p>
    </div>
    <a class="btn btn-secondary" href="profile.php?id=<?= e($user['id']) ?>"><i class="bi bi-person-badge"></i>View profile</a>
</div>

<?php if (!empty($gamification['enabled'])): ?>
<section class="grid grid-2 mb-3" id="gamification">
    <div class="card card-pad progress-card">
        <div class="card-head">
            <div><span class="eyebrow">Gamification</span><h2><?= e($gamification['level']['title'] ?? 'Code Sprout') ?></h2></div>
            <span class="pill pill-neutral">LEVEL <?= (int) ($gamification['level']['level'] ?? 1) ?></span>
        </div>
        <div class="metric-row">
            <label>XP</label>
            <div class="progress"><span style="width:<?= (int) $gamification['level_progress'] ?>%"></span></div>
            <b><?= (int) $gamification['xp'] ?></b>
        </div>
        <div class="mini-stat-grid mt-2">
            <div><span>Current streak</span><strong><?= (int) $gamification['current_streak'] ?>d</strong></div>
            <div><span>Best streak</span><strong><?= (int) $gamification['longest_streak'] ?>d</strong></div>
            <div><span>Badges</span><strong><?= (int) $gamification['earned_badge_count'] ?>/<?= count($gamification['badges']) ?></strong></div>
        </div>
        <?php if (!empty($gamification['next_level'])): ?>
            <div class="callout mt-2">Next level: <strong><?= e($gamification['next_level']['title']) ?></strong> at <?= (int) $gamification['next_level']['xp_required'] ?> XP.</div>
        <?php else: ?>
            <div class="callout mt-2"><strong>Maximum progression level reached.</strong></div>
        <?php endif; ?>
    </div>

    <div class="card card-pad">
        <div class="card-head">
            <div><span class="eyebrow">Achievements</span><h2>Badge cabinet</h2></div>
            <span class="pill pill-neutral"><?= (int) $gamification['earned_badge_count'] ?> EARNED</span>
        </div>
        <div class="achievement-grid">
            <?php foreach ($gamification['badges'] as $badge): $earned = !empty($badge['earned_at']); ?>
                <div class="achievement-item <?= $earned ? 'is-earned' : 'is-locked' ?>" title="<?= e($badge['description']) ?>">
                    <i class="bi <?= e($badge['icon']) ?>"></i>
                    <div><strong><?= e($badge['name']) ?></strong><small><?= e($earned ? 'Unlocked' : 'Locked') ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php else: ?>
<div class="toast-banner danger"><i class="bi bi-exclamation-triangle"></i>Gamification tables are not available yet. Run the PATCH-008 database migration.</div>
<?php endif; ?>

<section class="grid grid-2">
    <div class="card card-pad" id="quest-advisor">
        <div class="card-head">
            <div><span class="eyebrow">Quest Advisor</span><h2>Recommended next problems</h2></div>
            <span class="pill pill-neutral">UP TO 6</span>
        </div>
        <?php if (!empty($advisor['weakest'])): ?>
            <p class="muted small">Current weakest completion area: <strong class="text-cyan"><?= e($advisor['weakest']['topic']) ?></strong> · <?= (int) $advisor['weakest']['solved_count'] ?>/<?= (int) $advisor['weakest']['total_problems'] ?> solved.</p>
            <div class="list">
                <?php foreach ($advisor['recommendations'] as $rec): ?>
                    <a class="list-item" href="solve.php?id=<?= e($rec['id']) ?>">
                        <div><strong><?= e($rec['title']) ?></strong><br><small><?= e($rec['topic']) ?></small></div>
                        <span class="pill <?= difficulty_class($rec['difficulty']) ?>"><?= e($rec['difficulty']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$advisor['recommendations']): ?><div class="empty compact">This topic is already complete.</div><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty compact">No problem topics are available yet.</div>
        <?php endif; ?>
    </div>

    <div class="card card-pad" id="skill-tree">
        <div class="card-head">
            <div><span class="eyebrow">Skill Tree</span><h2>Topic mastery</h2></div>
            <span class="pill pill-neutral"><?= count($advisor['mastery']) ?> TOPICS</span>
        </div>
        <div class="skill-tree-list">
            <?php foreach ($advisor['mastery'] as $topicRow): ?>
                <div class="topic-bar">
                    <span class="name"><?= e($topicRow['topic']) ?><small class="muted"> <?= (int) $topicRow['solved_count'] ?>/<?= (int) $topicRow['total_problems'] ?></small></span>
                    <div class="progress"><span style="width:<?= (int) $topicRow['mastery_percent'] ?>%"></span></div>
                    <b><?= (int) $topicRow['mastery_percent'] ?>%</b>
                </div>
            <?php endforeach; ?>
            <?php if (!$advisor['mastery']): ?><div class="empty compact">No topic data available.</div><?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
