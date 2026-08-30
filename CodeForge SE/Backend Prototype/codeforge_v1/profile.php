<?php
session_start();
require_once 'config/db.php';
require_once 'includes/gamification.php';
include 'includes/header.php';

$id = $_GET['id'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>Hero not found.</div>";
    include 'includes/footer.php';
    exit;
}

$subStmt = $pdo->prepare("
    SELECT s.verdict, s.language, s.timestamp, p.title
    FROM Submissions s
    INNER JOIN Problems p ON s.problemId = p.id
    WHERE s.userId = ?
    ORDER BY s.timestamp DESC
    LIMIT 20
");
$subStmt->execute([$id]);
$submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);

$allBadges = getAllBadgesWithStatus($id, $pdo);
$level = getCurrentLevel($user['xp'], $pdo);
?>

<div class="container mt-5 fade-in">
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4 quest-card">
                <div class="card-body py-5 text-center">
                    <div class="display-1 mb-3">🧙‍♂️</div>
                    <h3 class="fw-bold mt-3"><?= htmlspecialchars($user['username']) ?></h3>
                    <p class="text-muted small">Hero ID: <?= htmlspecialchars($user['id']) ?></p>
                    <span class="badge bg-primary px-3 py-2 fs-6"><?= htmlspecialchars($user['rank'] ?? 'Newbie') ?></span>
                    <div class="mt-3">
                        <span class="display-4"><?= htmlspecialchars($level['icon'] ?? '🌱') ?></span>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($level['title'] ?? 'Code Sprout') ?></p>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 quest-card">
                <div class="card-body text-center">
                    <h6 class="text-uppercase small text-muted mb-1">Current Rating</h6>
                    <h2 class="fw-bold mb-1 text-primary"><?= (int)$user['rating'] ?></h2>
                    <div>
                        <?php for($i = 0; $i < min(5, floor($user['rating'] / 100)); $i++): ?>
                            <i class="bi bi-star-fill text-warning"></i>
                        <?php endfor; ?>
                    </div>
                    <h6 class="text-uppercase small text-muted mb-1 mt-3">Quests Conquered</h6>
                    <h4 class="fw-bold text-success"><?= (int)$user['solvedCount'] ?></h4>
                    <?php if (!empty($user['university'])): ?>
                        <p class="text-muted small mt-2 mb-0"><?= htmlspecialchars($user['university']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Quest History
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Quest</th>
                                <th>Verdict</th>
                                <th>Language</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($submissions)): ?>
                                <?php foreach ($submissions as $sub): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($sub['title']) ?></td>
                                        <td>
                                            <?php
                                            $badge = match(strtoupper($sub['verdict'])) {
                                                'AC' => 'bg-success',
                                                'WA' => 'bg-danger',
                                                'TLE' => 'bg-warning text-dark',
                                                'MLE' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                            $verdictIcon = match(strtoupper($sub['verdict'])) {
                                                'AC' => '✅',
                                                'WA' => '❌',
                                                'TLE' => '⏱️',
                                                'MLE' => '💾',
                                                default => '📝'
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $verdictIcon ?> <?= htmlspecialchars($sub['verdict']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($sub['language']) ?></td>
                                        <td class="text-muted small"><?= date('M d, Y H:i', strtotime($sub['timestamp'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No quests completed yet. Start your adventure!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($allBadges)): ?>
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-award text-warning me-2"></i> Trophy Case
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($allBadges as $badge):
                            $earned = !is_null($badge['earnedAt']);
                        ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card text-center h-100 <?= $earned ? 'border-warning quest-card' : 'border-secondary opacity-50' ?>">
                                    <div class="card-body py-3">
                                        <div class="achievement-badge <?= $earned ? 'earned' : 'locked' ?> mx-auto mb-2">
                                            <i class="bi <?= htmlspecialchars($badge['icon']) ?>"></i>
                                        </div>
                                        <h6 class="mt-2 mb-1 fw-bold"><?= htmlspecialchars($badge['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($badge['description']) ?></small>
                                        <div class="mt-2">
                                            <span class="badge <?= $earned ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $earned ? '🏆 Earned' : '🔒 Locked' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
