<?php 
require_once 'config/db.php'; 
include 'includes/header.php'; 

// 1. Get User Data
$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5 alert alert-danger'>User not found.</div>";
    exit;
}

// 2. Handle the Update Logic
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit User: <?= htmlspecialchars($user['username']) ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">User ID (Permanent)</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['id']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">University</label>
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
                                <label class="form-label fw-bold">Solved Count</label>
                                <input type="number" name="solvedCount" class="form-control" value="<?= (int)$user['solvedCount'] ?>">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="database.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
