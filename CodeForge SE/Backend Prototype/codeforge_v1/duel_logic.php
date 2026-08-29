<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// Determine the current user ("you") — from session if logged in, otherwise the demo default
$meId = $_SESSION['user_id'] ?? 'u1';
$rivalId = $_POST['rival'] ?? '';

if (!$rivalId) {
    echo "<div class='container mt-5 alert alert-warning'>No rival selected for the duel.</div>";
    include 'includes/footer.php';
    exit;
}

// Fetch both participants with prepared statements
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->execute([$meId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
$stmt2->execute([$rivalId]);
$rival = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$rival) {
    echo "<div class='container mt-5 alert alert-danger'>Rival not found.</div>";
    include 'includes/footer.php';
    exit;
}
?>

<div class="container mt-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold"><i class="bi bi-fire text-danger"></i> Head-to-Head Duel</h2>
        <p class="text-muted">You (<strong><?= htmlspecialchars($me['username'] ?? $meId) ?></strong>) vs <?= htmlspecialchars($rival['username']) ?></p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-header bg-primary text-center py-3">
                    <h4 class="mb-0">You</h4>
                    <small><?= htmlspecialchars($me['username'] ?? $meId) ?></small>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="text-muted d-block">Rating</span>
                        <h1 class="display-4 fw-bold text-primary"><?= (int)($me['rating'] ?? 0) ?></h1>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Solved</span>
                        <span class="fw-bold"><?= (int)($me['solvedCount'] ?? 0) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Rank</span>
                        <span class="fw-bold badge bg-light text-dark border"><?= htmlspecialchars($me['rank'] ?? 'Newbie') ?></span>
                    </div>
                    <p class="mt-3 badge bg-light text-dark border"><?= htmlspecialchars($me['university'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-2 d-flex align-items-center justify-content-center my-4 my-md-0">
            <h1 class="display-3 text-muted opacity-25">⚔️</h1>
        </div>

        <div class="col-md-6">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-header bg-danger text-center py-3">
                    <h4 class="mb-0">Rival</h4>
                    <small><?= htmlspecialchars($rival['username']) ?></small>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="text-muted d-block">Rating</span>
                        <h1 class="display-4 fw-bold text-danger"><?= (int)$rival['rating'] ?></h1>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Solved</span>
                        <span class="fw-bold"><?= (int)$rival['solvedCount'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Rank</span>
                        <span class="fw-bold badge bg-light text-dark border"><?= htmlspecialchars($rival['rank']) ?></span>
                    </div>
                    <p class="mt-3 badge bg-light text-dark border"><?= htmlspecialchars($rival['university'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="contests.php" class="btn btn-outline-secondary px-4"><i class="bi bi-arrow-left"></i> Back to Contests</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
