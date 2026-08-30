<?php 
require_once 'includes/gamification.php';
include 'config/db.php'; 
include 'includes/header.php'; 

$query = $_GET['q'] ?? '';
$query = trim($query);

if (empty($query)) {
    echo "<div class='container mt-5'><div class='alert alert-warning border-0 shadow-sm'>Please enter a search term.</div></div>";
    include 'includes/footer.php';
    exit;
}

$likeQuery = "%$query%";
?>

<div class="container mt-5 fade-in">
    <h3 class="mb-4">
        <i class="bi bi-search text-primary me-2"></i> Search Results for: 
        <span class="text-primary fw-bold">"<?= htmlspecialchars($query) ?>"</span>
    </h3>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 quest-card">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-people me-1"></i> Heroes
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT id, username, rating FROM Users WHERE username LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($users):
                        foreach ($users as $u): ?>
                            <a href="profile.php?id=<?= htmlspecialchars($u['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                                <span class="fw-bold"><?= htmlspecialchars($u['username']) ?></span>
                                <span class="badge bg-light text-dark border">⭐ <?= htmlspecialchars($u['rating']) ?></span>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No heroes found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 quest-card">
                <div class="card-header text-white fw-bold bg-success">
                    <i class="bi bi-swords me-1"></i> Quests
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT id, title, difficulty FROM Problems WHERE title LIKE :q OR topic LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($problems):
                        foreach ($problems as $p): ?>
                            <a href="solve.php?id=<?= htmlspecialchars($p['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                                <span class="fw-bold"><?= htmlspecialchars($p['title']) ?></span>
                                <small class="text-muted">
                                    <?php
                                    $difficulty = $p['difficulty'] ?? 'Easy';
                                    echo match($difficulty) {
                                        'Easy' => '🌱',
                                        'Medium' => '🌿',
                                        'Hard' => '🌳',
                                        default => '⭐'
                                    };
                                    ?>
                                </small>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No quests found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 quest-card">
                <div class="card-header text-white fw-bold bg-primary">
                    <i class="bi bi-building me-1"></i> Academies
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT name FROM Universities WHERE name LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $unis = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($unis):
                        foreach ($unis as $un): ?>
                            <a href="university_compare.php?u1=<?= urlencode($un['name']) ?>" class="list-group-item list-group-item-action">
                                📚 <?= htmlspecialchars($un['name']) ?>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No academies found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
