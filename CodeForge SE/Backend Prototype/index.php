<?php
session_start();
require_once 'config/db.php';
require_once 'includes/gamification.php';
include 'includes/header.php'; 

// 1. Session Protection: Redirect to login if no user is active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Define the active user ID from the session
$uId = $_SESSION['user_id']; 

// 3. Fetch User Summary
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
$stmt->execute([':id' => $uId]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>User not found.</div>";
    exit;
}

// 4. Identify Weakest Topic
$weakTopicStmt = $pdo->prepare("
    SELECT p.topic, COUNT(s.id) AS solved_count
    FROM Problems p
    LEFT JOIN Submissions s 
        ON p.id = s.problemId 
        AND s.userId = :uid 
        AND s.verdict = 'AC'
    GROUP BY p.topic
    ORDER BY solved_count ASC
    LIMIT 1
");
$weakTopicStmt->execute([':uid' => $uId]);
$weakTopicResult = $weakTopicStmt->fetch();
$weakestTopic = $weakTopicResult['topic'] ?? 'General';

// 5. Fetch Recommendations (3 unsolved problems in weakest topic)
$recStmt = $pdo->prepare("
    SELECT * FROM Problems 
    WHERE topic = :topic 
    AND id NOT IN (
        SELECT problemId FROM Submissions WHERE userId = :uid AND verdict = 'AC'
    )
    LIMIT 3
");
$recStmt->execute([':topic' => $weakestTopic, ':uid' => $uId]);
$recommendations = $recStmt->fetchAll();

// 6. Calculate Topic Mastery
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

// 7. Fetch Gamification Data
$level = getCurrentLevel($user['xp'], $pdo);
$nextLevel = getNextLevel($user['xp'], $pdo);
$xpProgress = getXpProgress($user['xp'], $pdo);
$earnedBadges = getUserBadges($uId, $pdo);
?>

<div class="container mt-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="fw-bold">Welcome, <?= htmlspecialchars($user['username']) ?>!</h1>
            <p class="text-muted">Viewing live analytics for User ID: <span class="badge bg-secondary"><?= htmlspecialchars($uId) ?></span></p>
        </div>
        <div class="col-md-4 text-end">
            <div class="p-3 bg-white shadow-sm rounded border-start border-4 border-primary">
                <small class="text-uppercase fw-bold text-muted d-block">Global Rating</small>
                <span class="h2 fw-bold text-primary"><?= htmlspecialchars($user['rating']) ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">
                        <i class="bi bi-rocket-takeoff text-warning"></i> Personalized Training
                    </h5>
                    <div class="alert alert-info border-0 bg-light mb-4">
                        Based on your history, you can improve by focusing on <strong><?= htmlspecialchars($weakestTopic) ?></strong>.
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recommendations)): ?>
                            <?php foreach($recommendations as $rec): ?>
                                <a href="solve.php?id=<?= htmlspecialchars($rec['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded mb-2 border shadow-sm">
                                    <div>
                                        <span class="fw-bold text-primary"><?= htmlspecialchars($rec['title']) ?></span>
                                        <div class="small text-muted">Topic: <?= htmlspecialchars($rec['topic']) ?></div>
                                    </div>
                                    <span class="btn btn-sm btn-outline-primary">Solve <i class="bi bi-chevron-right"></i></span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center p-3">
                                <i class="bi bi-stars text-success h1"></i>
                                <p class="text-muted mt-2">You've solved all recommended problems in this category!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-info"></i> Skill Proficiency</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($masteryResults as $m): 
                            $percentage = ($m['total_probs'] > 0) ? ($m['solved_count'] / $m['total_probs']) * 100 : 0;
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold"><?= htmlspecialchars($m['topic']) ?></span>
                                    <span class="small text-muted"><?= htmlspecialchars($m['solved_count']) ?> / <?= htmlspecialchars($m['total_probs']) ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-info progress-bar-striped" style="width: <?= $percentage ?>%"></div>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-stars text-warning"></i> Your Progress</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h6 class="text-uppercase fw-bold text-muted small">Level</h6>
                        <h4 class="fw-bold text-primary"><?= htmlspecialchars($level['title'] ?? 'Code Sprout') ?></h4>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-warning" style="width: <?= $xpProgress ?>%"></div>
                        </div>
                        <small class="text-muted"><?= (int)$user['xp'] ?> / <?= (int)($nextLevel['xp_required'] ?? 'MAX') ?> XP</small>
                    </div>

                    <hr>

                    <div class="text-center mb-3">
                        <h6 class="text-uppercase fw-bold text-muted small">Streak</h6>
                        <h4 class="fw-bold text-danger">
                            <i class="bi bi-fire"></i> <?= (int)$user['current_streak'] ?> days
                        </h4>
                        <?php if ((int)$user['longest_streak'] > 0): ?>
                            <small class="text-muted">Best: <?= (int)$user['longest_streak'] ?> days</small>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-2">Badges</h6>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php foreach ($earnedBadges as $badge): ?>
                                <span class="badge bg-warning text-dark p-2" title="<?= htmlspecialchars($badge['description']) ?>">
                                    <i class="bi <?= htmlspecialchars($badge['icon']) ?>"></i>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($earnedBadges)): ?>
                                <span class="text-muted small">Keep solving to earn badges!</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm bg-dark text-white mb-4">
                <div class="card-body text-center py-4">
                    <h6 class="text-uppercase opacity-50 small">Quests Solved</h6>
                    <h1 class="display-2 fw-bold mb-0"><?= htmlspecialchars($user['solvedCount']) ?></h1>
                    <div class="mt-3">
                        <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars($user['rank']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
