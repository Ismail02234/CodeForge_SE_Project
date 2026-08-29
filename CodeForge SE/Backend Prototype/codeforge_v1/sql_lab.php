<?php 
session_start();
require_once 'config/db.php'; 
include 'includes/header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$uId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM Users WHERE id = ?");
$stmt->execute([$uId]);
$user = $stmt->fetch();
if (!$user || $user['role'] !== 'admin') {
    header("Location: index.php?error=unauthorized");
    exit();
}

$query = $_POST['query'] ?? "";
$columns = [];
$rows = [];
$error = null;
$affected = null;

if (!empty($query)) {
    try {
        if (stripos(trim($query), 'SELECT') === 0 && stripos($query, 'LIMIT') === false) {
            $query .= " LIMIT 200";
        }

        $stmt = $pdo->query($query);

        if ($stmt instanceof PDOStatement) {
            $colCount = $stmt->columnCount();
            if ($colCount > 0) {
                for ($i = 0; $i < $colCount; $i++) {
                    $meta = $stmt->getColumnMeta($i);
                    $columns[] = $meta['name'];
                }
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $affected = $stmt->rowCount();
            }
        }

    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container mt-5 fade-in">
    <h3 class="fw-bold mb-4 text-primary">
        <i class="bi bi-wand-magic-sparkles me-2"></i> Magic Spell Lab
    </h3>
    <p class="text-muted mb-4">Cast powerful SQL spells to reveal hidden knowledge from the treasure vault!</p>
    
    <div class="card shadow-sm border-0 mb-4 bg-dark text-white">
        <div class="card-body p-4">
            <form method="POST">
                <label class="form-label text-info fw-bold">
                    <i class="bi bi-terminal me-1"></i> Enter Your Spell (SQL Query):
                </label>
                <textarea name="query" class="form-control bg-black text-success font-monospace mb-3" rows="5" placeholder="Write your magical SQL spell here..."><?= htmlspecialchars($query) ?></textarea>
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-magic me-1"></i> Cast Spell
                </button>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i> <strong>Spell Failed:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (!empty($columns)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-table me-2 text-success"></i> Spell Results
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped bg-white mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th class="text-primary"><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $val): ?>
                                    <td><?= htmlspecialchars($val) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($affected !== null): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-4">
                <div class="display-1 mb-2">✨</div>
                <h5 class="fw-bold text-success">Spell Executed Successfully!</h5>
                <p class="text-muted">Affected rows: <strong class="text-primary"><?= $affected ?></strong></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
