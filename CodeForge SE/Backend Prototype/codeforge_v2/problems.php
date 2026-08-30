<?php 
require_once 'includes/gamification.php';
include 'config/db.php'; 
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-list-check"></i> Practice Arena</h2>
        <?php if(isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success py-1">Submission Successful!</div>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php
        // Fetch all problems using PDO
        $stmt = $pdo->query("SELECT * FROM Problems ORDER BY id ASC");
        $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($problems as $row):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($row['topic']) ?></h6>

                    <!-- Optional: dynamically show difficulty if available -->
                    <?php 
                        $difficulty = $row['difficulty'] ?? 'Easy'; 
                        $badgeClass = match($difficulty) {
                            'Easy' => 'bg-success',
                            'Medium' => 'bg-warning text-dark',
                            'Hard' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        $kidLabel = difficultyKidLabel($difficulty);
                    ?>
                    <span class="badge <?= $badgeClass ?> mb-3"><?= htmlspecialchars($difficulty) ?> (<?= htmlspecialchars($kidLabel) ?>)</span>

                    <p class="card-text">Solved by: <strong><?= htmlspecialchars($row['solvedBy']) ?></strong> users</p>
                    <a href="solve.php?id=<?= $row['id'] ?>" class="btn btn-primary w-100">Attempt Quest</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
