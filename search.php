<?php 
require_once 'includes/gamification.php';
include 'config/db.php'; 
include 'includes/header.php'; 

// 1. Get search query
$query = $_GET['q'] ?? '';
$query = trim($query);

if (empty($query)) {
    echo "<div class='container mt-5'><div class='alert alert-warning'>Please enter a search term.</div></div>";
    include 'includes/footer.php';
    exit;
}

// 2. Prepare search term for LIKE operator
$likeQuery = "%$query%";
?>

<div class="container mt-5">
    <h3 class="mb-4">Search Results for: <span class="text-primary">"<?= htmlspecialchars($query) ?>"</span></h3>

    <div class="row">
        <!-- Users -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white"><i class="bi bi-people"></i> Users</div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT id, username, rating FROM Users WHERE username LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($users):
                        foreach ($users as $u): ?>
                            <a href="profile.php?id=<?= htmlspecialchars($u['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                                <?= htmlspecialchars($u['username']) ?> 
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($u['rating']) ?></span>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No users found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>

        <!-- Problems -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white"><i class="bi bi-code-slash"></i> Quests</div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT id, title, difficulty FROM Problems WHERE title LIKE :q OR topic LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($problems):
                        foreach ($problems as $p): ?>
                            <a href="solve.php?id=<?= htmlspecialchars($p['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                                <?= htmlspecialchars($p['title']) ?> 
                                <small class="text-muted"><?= htmlspecialchars($p['difficulty']) ?> (<?= htmlspecialchars(difficultyKidLabel($p['difficulty'])) ?>)</small>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No problems found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>

        <!-- Universities -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><i class="bi bi-bank"></i> Universities</div>
                <div class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT name FROM Universities WHERE name LIKE :q LIMIT 5");
                    $stmt->execute(['q' => $likeQuery]);
                    $unis = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($unis):
                        foreach ($unis as $un): ?>
                            <a href="university_compare.php?u1=<?= urlencode($un['name']) ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($un['name']) ?>
                            </a>
                        <?php endforeach;
                    else:
                        echo "<div class='p-3 text-muted small'>No universities found.</div>";
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
