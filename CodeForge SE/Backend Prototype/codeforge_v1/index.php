<?php
session_start();
require_once 'config/db.php';
require_once 'includes/gamification.php';
require_once 'includes/recommendation_engine.php';
include 'includes/header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
$stmt->execute([':id' => $uId]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>Hero not found.</div>";
    exit;
}

$rec = getRecommendations($uId, $pdo, 3);
$recommendations = $rec['problems'];
$recExplanation = $rec['explanation'] ?? 'Keep solving to build your skill profile!';

$masteryStmt = $pdo->prepare("
    SELECT p.topic,
           COUNT(p.id) AS total_probs,
           (
               SELECT COUNT(DISTINCT s.problemId)
               FROM Submissions s
               WHERE s.userId = :uid
               AND s.problemId IN (SELECT id FROM Problems WHERE topic = p.topic)
               AND s.verdict = 'AC'
           ) AS solved_count
    FROM Problems p
    GROUP BY p.topic
");
$masteryStmt->execute([':uid' => $uId]);
$masteryResults = $masteryStmt->fetchAll();

$level = getCurrentLevel($user['xp'], $pdo);
$nextLevel = getNextLevel($user['xp'], $pdo);
$xpProgress = getXpProgress($user['xp'], $pdo);
$earnedBadges = getUserBadges($uId, $pdo);
?>

<div class="container mt-5 fade-in">
    <div class="hero-welcome mb-5 bounce-in">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="mascot">🧙‍♂️</span>
                    <h1 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars($user['username']) ?>!</h1>
                </div>
                <p class="text-muted mb-0">
                    Your adventure continues... Ready to level up your coding powers?
                </p>
            </div>
            <div class="col-md-4 text-end mt-3 mt-md-0">
                <div class="hero-stat-card">
                    <small class="text-uppercase fw-bold text-muted d-block">Hero Rating</small>
                    <span class="display-4 fw-bold text-primary"><?= htmlspecialchars($user['rating']) ?></span>
                    <div class="mt-1">
                        <i class="bi bi-star-fill text-warning"></i>
                        <i class="bi bi-star-fill text-warning"></i>
                        <i class="bi bi-star-fill text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4 quest-card">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">
                        <i class="bi bi-compass text-primary me-2"></i> Quest Advisor
                    </h5>
                    <div class="alert border-0 mb-4" style="background: var(--primary-light); border: 2px dashed var(--primary) !important;">
                        <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                        <?= htmlspecialchars($recExplanation) ?>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recommendations)): ?>
                            <?php foreach($recommendations as $rec): ?>
                                <a href="solve.php?id=<?= htmlspecialchars($rec['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded mb-2 border shadow-sm">
                                    <div>
                                        <span class="fw-bold text-primary"><?= htmlspecialchars($rec['title']) ?></span>
                                        <div class="small text-muted">Topic: <?= htmlspecialchars($rec['topic']) ?></div>
                                    </div>
                                    <span class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-sword me-1"></i> Quest
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <span class="display-1">🏆</span>
                                <p class="text-muted mt-3 fs-5">Incredible! You've conquered all recommended quests in this realm!</p>
                                <a href="problems.php" class="btn btn-primary mt-2">Explore More Quests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-diagram-3 text-info me-2"></i> Skill Tree
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($masteryResults as $m): 
                            $percentage = ($m['total_probs'] > 0) ? ($m['solved_count'] / $m['total_probs']) * 100 : 0;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="skill-tree-item">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small fw-bold text-primary">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                            <?= htmlspecialchars($m['topic']) ?>
                                        </span>
                                        <span class="small text-muted fw-bold"><?= htmlspecialchars($m['solved_count']) ?> / <?= htmlspecialchars($m['total_probs']) ?></span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info progress-bar-striped" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block"><?= round($percentage) ?>% Mastered</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-star text-warning me-2"></i> Hero Progress
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-2">
                            <span class="display-4"><?= htmlspecialchars($level['icon'] ?? '🌱') ?></span>
                        </div>
                        <h6 class="text-uppercase fw-bold text-muted small">Current Rank</h6>
                        <h4 class="fw-bold text-primary"><?= htmlspecialchars($level['title'] ?? 'Code Sprout') ?></h4>
                        <div class="progress mt-3" style="height: 12px;">
                            <div class="progress-bar bg-warning" style="width: <?= $xpProgress ?>%"></div>
                        </div>
                        <small class="text-muted"><?= (int)$user['xp'] ?> / <?= (int)($nextLevel['xp_required'] ?? 'MAX') ?> XP</small>
                    </div>

                    <hr class="my-4">

                    <div class="text-center mb-4">
                        <h6 class="text-uppercase fw-bold text-muted small">Streak Power</h6>
                        <h4 class="fw-bold text-danger">
                            <span class="streak-fire">🔥</span> <?= (int)$user['current_streak'] ?> days
                        </h4>
                        <?php if ((int)$user['longest_streak'] > 0): ?>
                            <small class="text-muted">Best: <?= (int)$user['longest_streak'] ?> days</small>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-3">Trophies</h6>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php foreach ($earnedBadges as $badge): ?>
                                <span class="achievement-badge earned" title="<?= htmlspecialchars($badge['description']) ?>">
                                    <i class="bi <?= htmlspecialchars($badge['icon']) ?>"></i>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($earnedBadges)): ?>
                                <span class="text-muted small">Solve quests to earn trophies!</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm reward-card mb-4">
                <div class="card-body py-4">
                    <h6 class="text-uppercase opacity-75 small fw-bold">Quests Completed</h6>
                    <div class="reward-amount"><?= htmlspecialchars($user['solvedCount']) ?></div>
                    <div class="mt-2">
                        <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars($user['rank']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
