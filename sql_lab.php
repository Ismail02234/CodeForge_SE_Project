<?php 
session_start();
require_once 'config/db.php'; 
include 'includes/header.php'; 

// 🔒 ROLE CHECK: Only admins allowed
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

// ⚠️ PDO is configured with ERRMODE_EXCEPTION in config/db.php, so errors throw automatically
$query = $_POST['query'] ?? "";
$columns = [];
$rows = [];
$error = null;
$affected = null;

if (!empty($query)) {
    try {
        // Optional: Limit SELECT results to prevent huge tables
        if (stripos(trim($query), 'SELECT') === 0 && stripos($query, 'LIMIT') === false) {
            $query .= " LIMIT 200";
        }

        $stmt = $pdo->query($query);

        // Determine whether this is a result-bearing (SELECT) query
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

<div class="container mt-5">
    <h3 class="fw-bold mb-4 text-danger font-monospace"><i class="bi bi-terminal"></i> Root SQL Console</h3>
    
    <!-- Query Input Card -->
    <div class="card shadow-sm bg-dark text-white p-4 mb-4">
        <form method="POST">
            <label class="form-label text-info">Execute Raw Query:</label>
            <textarea name="query" class="form-control bg-black text-success font-monospace mb-3" rows="5" placeholder="Enter your SQL here..."><?= htmlspecialchars($query) ?></textarea>
            <button type="submit" class="btn btn-info w-100 fw-bold">Run Query</button>
        </form>
    </div>

    <!-- Error Display -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><strong>Error:</strong> <?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($columns)): ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
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
    <?php elseif ($affected !== null): ?>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-light">
                    <tr><th>Result</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center py-3">Query executed successfully. Affected rows: <?= $affected ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
