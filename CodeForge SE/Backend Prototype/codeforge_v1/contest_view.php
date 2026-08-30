<?php
session_start();
require_once 'config/db.php';
require_once 'includes/gamification.php';
include 'includes/header.php';

$cid = $_GET['id'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM Contests WHERE id = ?");
$stmt->execute([$cid]);
$contest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contest) {
    echo "<div class='container mt-5 alert alert-danger'>Tournament not found.</div>";
    include 'includes/footer.php';
    exit;
}

$probStmt = $pdo->prepare("
    SELECT p.id, p.title, p.topic, p.difficulty, p.solvedBy
    FROM Problems p
    INNER JOIN contest_problems cp ON p.id = cp.problemId
    WHERE cp.contestId = ?
    ORDER BY p.id ASC
");
$probStmt->execute([$cid]);
$problems = $probStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 fade-in">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold">
                <i class="bi bi-trophy text-warning me-2"></i> <?= htmlspecialchars($contest['name']) ?>
            </h2>
            <p class="text-muted mb-0">
                <span class="badge bg-<?= ($contest['status'] === 'Active') ? 'success' : (($contest['status'] === 'Upcoming') ? 'primary' : 'secondary') ?>">
                    <?= htmlspecialchars($contest['status']) ?>
                </span>
                &middot; <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($contest['date'])) ?>
                &middot; <i class="bi bi-people"></i> <?= (int)$contest['participants'] ?> heroes
                &middot; Type: <?= htmlspecialchars($contest['type']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($contest['status'] !== 'Past'): ?>
                <button class="btn btn-success px-4 fw-bold" disabled>
                    <i class="bi bi-play me-1"></i> Enter Tournament
                </button>
            <?php else: ?>
                <button class="btn btn-outline-secondary px-4" disabled>Tournament Ended</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            <i class="bi bi-swords me-2 text-primary"></i> Tournament Quests
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Quest</th>
                        <th>Topic</th>
                        <th>Difficulty</th>
                        <th>Solved</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($problems)): ?>
                        <?php foreach ($problems as $p): ?>
                            <tr>
                                <td class="text-muted small"><?= htmlspecialchars($p['id']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($p['title']) ?></td>
                                <td><?= htmlspecialchars($p['topic']) ?></td>
                                <td>
                                    <?php
                                    $difficulty = $p['difficulty'] ?? 'Easy';
                                    $badgeClass = match($difficulty) {
                                        'Easy' => 'difficulty-sprout',
                                        'Medium' => 'difficulty-sapling',
                                        'Hard' => 'difficulty-oak',
                                        default => 'bg-secondary'
                                    };
                                    $emoji = match($difficulty) {
                                        'Easy' => '🌱',
                                        'Medium' => '🌿',
                                        'Hard' => '🌳',
                                        default => '⭐'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $emoji ?> <?= htmlspecialchars($difficulty) ?> (<?= htmlspecialchars(difficultyKidLabel($difficulty)) ?>)</span>
                                </td>
                                <td class="text-muted small"><?= (int)$p['solvedBy'] ?> <i class="bi bi-people"></i></td>
                                <td>
                                    <a href="solve.php?id=<?= htmlspecialchars($p['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-sword me-1"></i> Quest
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No quests have been added to this tournament yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
