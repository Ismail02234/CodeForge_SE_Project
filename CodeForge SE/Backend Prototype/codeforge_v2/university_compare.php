<?php 
include 'config/db.php'; 
include 'includes/header.php'; 

// 1. Get the first university from GET or default to the first in DB
$u1_name = $_GET['u1'] ?? '';

// 2. Fetch all universities for the rival selection (array, so we can iterate twice)
$univList = $pdo->query("SELECT name FROM Universities ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

// 3. Get second university from GET
$u2_name = $_GET['u2'] ?? '';

// Helper: fetch live stats via a prepared statement (no string interpolation)
function getLiveUnivStats($pdo, $name) {
    if (!$name) return null;
    $sql = "SELECT 
                COUNT(id) as student_count, 
                COALESCE(SUM(solvedCount),0) as total_solves,
                COALESCE(AVG(rating),0) as avg_rating
            FROM Users 
            WHERE university = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$u1_stats = getLiveUnivStats($pdo, $u1_name);
$u2_stats = getLiveUnivStats($pdo, $u2_name);
?>

<div class="container mt-5">
    <h2 class="text-center mb-5 fw-bold">Institutional Comparison</h2>

    <div class="row justify-content-center mb-5">
        <div class="col-md-10">
            <div class="card shadow-sm border-0 bg-dark text-white p-4">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="small text-uppercase text-info">Comparing</label>
                        <input type="text" name="u1" class="form-control bg-secondary text-white border-0" 
                               value="<?= htmlspecialchars($u1_name) ?>" readonly>
                    </div>
                    <div class="col-md-2 text-center mt-4">
                        <h3 class="mb-0">VS</h3>
                    </div>
                    <div class="col-md-5">
                        <label class="small text-uppercase text-info">Select Rival</label>
                        <select name="u2" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Choose University --</option>
                            <?php foreach ($univList as $row): 
                                if ($row['name'] === $u1_name) continue; // skip the selected one
                            ?>
                                <option value="<?= htmlspecialchars($row['name']) ?>" <?= ($u2_name == $row['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($u1_stats && $u2_stats): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="text-primary"><?= htmlspecialchars($u1_name) ?></h4>
                    <hr>
                    <table class="table table-borderless">
                        <tr><td>Students:</td><td class="fw-bold"><?= $u1_stats['student_count'] ?></td></tr>
                        <tr><td>Total Solves:</td><td class="fw-bold text-success"><?= $u1_stats['total_solves'] ?></td></tr>
                        <tr><td>Avg Rating:</td><td class="fw-bold"><?= round($u1_stats['avg_rating'], 1) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="text-danger"><?= htmlspecialchars($u2_name) ?></h4>
                    <hr>
                    <table class="table table-borderless">
                        <tr><td>Students:</td><td class="fw-bold"><?= $u2_stats['student_count'] ?></td></tr>
                        <tr><td>Total Solves:</td><td class="fw-bold text-success"><?= $u2_stats['total_solves'] ?></td></tr>
                        <tr><td>Avg Rating:</td><td class="fw-bold"><?= round($u2_stats['avg_rating'], 1) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
