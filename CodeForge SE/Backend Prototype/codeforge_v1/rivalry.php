<?php 
include 'config/db.php'; 
include 'includes/header.php'; 

$stmt = $pdo->query("SELECT id, username FROM Users ORDER BY username ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$u1_id = $_GET['u1'] ?? ($users[0]['id'] ?? '');
$u2_id = $_GET['u2'] ?? ($users[1]['id'] ?? '');

function fetchUser($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$u1_data = fetchUser($pdo, $u1_id);
$u2_data = fetchUser($pdo, $u2_id);
?>

<div class="container mt-5 fade-in">
    <div class="text-center mb-5">
        <h2 class="fw-bold">
            <i class="bi bi-lightning text-warning me-2"></i> Duel Arena
        </h2>
        <p class="text-muted">Compare hero stats from the realm's greatest adventurers!</p>
    </div>
    
    <form class="row g-3 mb-5 justify-content-center" method="GET">
        <div class="col-md-4">
            <label class="form-label fw-bold">
                <i class="bi bi-shield-star text-primary me-1"></i> Hero 1
            </label>
            <select name="u1" class="form-select border-primary" onchange="this.form.submit()">
                <?php foreach($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($u1_id == $user['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 text-center d-none d-md-block" style="margin-top: 45px;">
            <span class="badge bg-dark rounded-circle p-2 fs-5">⚔️</span>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">
                <i class="bi bi-shield-star text-danger me-1"></i> Hero 2
            </label>
            <select name="u2" class="form-select border-danger" onchange="this.form.submit()">
                <?php foreach($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($u2_id == $user['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($u1_data && $u2_data): ?>
    <div class="row">
        <?php 
        $usersCompare = [
            ['data' => $u1_data, 'color' => 'primary', 'emoji' => '🛡️'],
            ['data' => $u2_data, 'color' => 'danger', 'emoji' => '⚔️']
        ];
        foreach($usersCompare as $index => $userInfo):
        ?>
            <?php if($index === 1): ?>
                <div class="col-md-2 d-flex align-items-center justify-content-center my-4 my-md-0">
                    <div class="vr d-none d-md-block h-100"></div>
                    <h1 class="display-3 text-muted opacity-25 mx-3">⚔️</h1>
                    <div class="vr d-none d-md-block h-100"></div>
                </div>
            <?php endif; ?>

            <div class="col-md-5">
                <div class="card shadow-lg border-0 h-100 quest-card" style="border-top: 4px solid var(--<?= $userInfo['color'] ?>) !important;">
                    <div class="card-header bg-<?= $userInfo['color'] ?> text-white text-center py-4">
                        <div class="display-3 mb-2"><?= $userInfo['emoji'] ?></div>
                        <h4 class="mb-0 fw-bold"><?= htmlspecialchars($userInfo['data']['username']) ?></h4>
                        <small><?= htmlspecialchars($userInfo['data']['university']) ?></small>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="mb-4">
                            <span class="text-muted d-block small text-uppercase fw-bold">Hero Rating</span>
                            <h1 class="display-4 fw-bold text-<?= $userInfo['color'] ?>"><?= $userInfo['data']['rating'] ?></h1>
                            <div>
                                <?php for($i = 0; $i < min(5, floor($userInfo['data']['rating'] / 100)); $i++): ?>
                                    <i class="bi bi-star-fill text-warning"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">Quests Conquered</span>
                                <span class="fw-bold text-<?= $userInfo['color'] ?>"><?= $userInfo['data']['solvedCount'] ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-<?= $userInfo['color'] ?>" style="width: <?= min(($userInfo['data']['solvedCount']/500)*100, 100) ?>%"></div>
                            </div>
                        </div>
                        <p class="badge bg-light text-dark border mt-3 px-3 py-2 fs-6"><?= htmlspecialchars($userInfo['data']['rank']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
