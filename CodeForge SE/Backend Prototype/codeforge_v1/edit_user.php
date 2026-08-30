<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>Hero not found.</div>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_name = $_POST['username'];
    $new_univ = $_POST['university'];
    $new_rating = $_POST['rating'];
    $new_solved = $_POST['solvedCount'];

    $updateStmt = $pdo->prepare("
        UPDATE Users SET 
            username = :username, 
            university = :university, 
            rating = :rating, 
            solvedCount = :solvedCount 
        WHERE id = :id
    ");

    $success = $updateStmt->execute([
        ':username' => $new_name,
        ':university' => $new_univ,
        ':rating' => $new_rating,
        ':solvedCount' => $new_solved,
        ':id' => $id
    ]);

    if ($success) {
        echo "<script>window.location.href='database.php?status=updated';</script>";
        exit;
    }
}
?>

<div class="container mt-5 fade-in">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0 quest-card">
                <div class="card-header text-white fw-bold bg-primary">
                    <i class="bi bi-person-gear me-2"></i> Edit Hero: <?= htmlspecialchars($user['username']) ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Hero ID (Permanent)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['id']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hero Name</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Academy</label>
                            <select name="university" class="form-select">
                                <?php 
                                $stmt = $pdo->query("SELECT name FROM Universities");
                                $univs = $stmt->fetchAll();
                                foreach ($univs as $un): ?>
                                    <option value="<?= htmlspecialchars($un['name']) ?>" <?= ($un['name'] === $user['university']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($un['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Rating</label>
                                <input type="number" name="rating" class="form-control" value="<?= (int)$user['rating'] ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Quests Conquered</label>
                                <input type="number" name="solvedCount" class="form-control" value="<?= (int)$user['solvedCount'] ?>">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="database.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
