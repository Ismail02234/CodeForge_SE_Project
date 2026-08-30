<?php 
require_once 'includes/gamification.php';
include 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-map text-primary me-2"></i> Quest Board
            </h2>
            <p class="text-muted mb-0">Choose your next adventure and conquer the code!</p>
        </div>
        <?php if(isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success py-2 px-4 border-0 shadow-sm">
                <i class="bi bi-check-circle me-1"></i> Submission Successful!
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php
        $stmt = $pdo->query("SELECT * FROM Problems ORDER BY id ASC");
        $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($problems as $row):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm quest-card">
                <div class="card-body">
                    <div class="quest-icon">
                        <?php
                        $difficulty = $row['difficulty'] ?? 'Easy';
                        $icon = match($difficulty) {
                            'Easy' => '🌱',
                            'Medium' => '🌿',
                            'Hard' => '🌳',
                            default => '⭐'
                        };
                        echo $icon;
                        ?>
                    </div>
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($row['title']) ?></h5>
                    <h6 class="card-subtitle mb-3 text-muted">
                        <i class="bi bi-tag me-1"></i> <?= htmlspecialchars($row['topic']) ?>
                    </h6>

                    <?php 
                        $difficulty = $row['difficulty'] ?? 'Easy'; 
                        $badgeClass = match($difficulty) {
                            'Easy' => 'difficulty-sprout',
                            'Medium' => 'difficulty-sapling',
                            'Hard' => 'difficulty-oak',
                            default => 'bg-secondary'
                        };
                        $kidLabel = difficultyKidLabel($difficulty);
                        $emoji = match($difficulty) {
                            'Easy' => '🌱',
                            'Medium' => '🌿',
                            'Hard' => '🌳',
                            default => '⭐'
                        };
                    ?>
                    <span class="badge <?= $badgeClass ?> mb-3">
                        <?= $emoji ?> <?= htmlspecialchars($difficulty) ?> (<?= htmlspecialchars($kidLabel) ?>)
                    </span>

                    <p class="card-text text-muted small mb-3">
                        <i class="bi bi-people me-1"></i> 
                        Solved by: <strong class="text-dark"><?= htmlspecialchars($row['solvedBy']) ?></strong> heroes
                    </p>
                    <a href="solve.php?id=<?= $row['id'] ?>" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-sword me-1"></i> Accept Quest
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
