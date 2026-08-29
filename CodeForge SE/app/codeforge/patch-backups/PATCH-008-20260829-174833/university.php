<?php
require_once __DIR__ . '/core/bootstrap.php';
require_login($pdo);

$rows = $pdo->query(
    "SELECT un.name,
            un.city,
            COUNT(u.id) AS students,
            COALESCE(ROUND(AVG(u.rating), 0), 0) AS avg_rating,
            COALESCE(SUM(COALESCE(stats.solved_count, 0)), 0) AS total_solves,
            COALESCE(MAX(u.rating), 0) AS top_rating
     FROM universities un
     LEFT JOIN users u ON u.university = un.name
     LEFT JOIN (
         SELECT user_id, COUNT(DISTINCT problem_id) AS solved_count
         FROM submissions
         WHERE verdict = 'AC'
         GROUP BY user_id
     ) stats ON stats.user_id = u.id
     GROUP BY un.name, un.city
     ORDER BY avg_rating DESC, total_solves DESC, un.name ASC"
)->fetchAll();

$pageTitle = 'Universities';
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div><div class="eyebrow">Institution Analytics</div><h1>University Leaderboard</h1><p>Institution statistics are calculated dynamically from users and accepted submissions instead of relying on stale stored totals.</p></div>
    <a class="btn btn-secondary" href="university_compare.php"><i class="bi bi-bar-chart"></i>Compare universities</a>
</div>
<div class="grid grid-3">
<?php foreach ($rows as $i => $row): ?>
    <div class="card card-pad feature-card">
        <div class="card-head"><span class="pill pill-neutral">#<?= $i + 1 ?></span><small class="muted"><?= e($row['city'] ?? '') ?></small></div>
        <h3><?= e($row['name']) ?></h3>
        <div class="grid grid-3 mt-2">
            <div><small class="muted">Students</small><div class="metric-number"><?= (int) $row['students'] ?></div></div>
            <div><small class="muted">Solves</small><div class="metric-number text-green"><?= (int) $row['total_solves'] ?></div></div>
            <div><small class="muted">Avg rating</small><div class="metric-number text-cyan"><?= (int) $row['avg_rating'] ?></div></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
