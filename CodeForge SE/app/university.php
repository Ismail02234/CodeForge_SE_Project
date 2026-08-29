<?php
require_once __DIR__ . '/core/bootstrap.php';
require_login($pdo);

$rows = $pdo->query(
    "WITH solved AS (
         SELECT user_id, COUNT(DISTINCT problem_id) AS solved_count
         FROM submissions
         WHERE verdict = 'AC'
         GROUP BY user_id
     ),
     user_stats AS (
         SELECT u.id, u.username, u.university, u.rating,
                COALESCE(s.solved_count, 0) AS solved_count
         FROM users u
         LEFT JOIN solved s ON s.user_id = u.id
     ),
     ranked AS (
         SELECT us.*,
                ROW_NUMBER() OVER (
                    PARTITION BY us.university
                    ORDER BY us.solved_count DESC, us.rating DESC, us.username ASC
                ) AS solver_rank
         FROM user_stats us
         WHERE us.university IS NOT NULL
     )
     SELECT un.name,
            un.city,
            COUNT(us.id) AS students,
            COALESCE(ROUND(AVG(us.rating), 0), 0) AS avg_rating,
            COALESCE(SUM(us.solved_count), 0) AS total_solves,
            COALESCE(MAX(us.rating), 0) AS top_rating,
            top.id AS top_user_id,
            top.username AS top_username,
            COALESCE(top.solved_count, 0) AS top_solved
     FROM universities un
     LEFT JOIN user_stats us ON us.university = un.name
     LEFT JOIN ranked top ON top.university = un.name AND top.solver_rank = 1
     GROUP BY un.name, un.city, top.id, top.username, top.solved_count
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
        <div class="top-solver mt-2">
            <span class="eyebrow">Top solver</span>
            <?php if($row['top_user_id']): ?>
                <a href="profile.php?id=<?= e($row['top_user_id']) ?>"><i class="bi bi-trophy-fill text-amber"></i><strong><?= e($row['top_username']) ?></strong><small><?= (int)$row['top_solved'] ?> solved</small></a>
            <?php else: ?>
                <span class="muted small">No coders yet.</span>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
