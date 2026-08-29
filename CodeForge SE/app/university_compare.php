<?php
require_once __DIR__ . '/core/bootstrap.php';
require_login($pdo);

$universities = $pdo->query('SELECT name, city FROM universities ORDER BY name')->fetchAll();
$names = array_column($universities, 'name');
$a = trim((string)($_GET['a'] ?? ($names[0] ?? '')));
$b = trim((string)($_GET['b'] ?? ($names[1] ?? ($names[0] ?? ''))));
if (!in_array($a, $names, true)) $a = $names[0] ?? '';
if (!in_array($b, $names, true)) $b = $names[1] ?? ($names[0] ?? '');
if ($a === $b && count($names) > 1) {
    foreach ($names as $name) { if ($name !== $a) { $b = $name; break; } }
}

function university_metrics(PDO $pdo, string $name): ?array
{
    $stmt = $pdo->prepare(
        "SELECT un.name, un.city,
                COUNT(u.id) AS students,
                COALESCE(ROUND(AVG(u.rating),0),0) AS avg_rating,
                COALESCE(SUM(COALESCE(stats.solved_count,0)),0) AS total_solves,
                COALESCE(MAX(u.rating),0) AS top_rating
         FROM universities un
         LEFT JOIN users u ON u.university=un.name
         LEFT JOIN (
             SELECT user_id, COUNT(DISTINCT problem_id) AS solved_count
             FROM submissions
             WHERE verdict='AC'
             GROUP BY user_id
         ) stats ON stats.user_id=u.id
         WHERE un.name=:name
         GROUP BY un.name,un.city"
    );
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $top = $pdo->prepare("SELECT username,rating FROM users WHERE university=:name ORDER BY rating DESC, username ASC LIMIT 1");
    $top->execute(['name' => $name]);
    $row['top_user'] = $top->fetch() ?: null;
    return $row;
}

$left = $a !== '' ? university_metrics($pdo, $a) : null;
$right = $b !== '' ? university_metrics($pdo, $b) : null;
$pageTitle = 'University Comparison';
include __DIR__ . '/includes/header.php';
?>
<div class="page">
    <div class="page-head"><div><span class="eyebrow">Institution Analytics</span><h1 class="page-title">University Comparison</h1><p class="page-subtitle">Compare live student count, accepted solves and average rating directly from relational records.</p></div><a class="btn btn-secondary" href="university.php"><i class="bi bi-arrow-left"></i>Leaderboard</a></div>
    <form class="card filter-bar" method="get">
        <select class="form-select" name="a"><?php foreach($universities as $u): ?><option value="<?= e($u['name']) ?>" <?= $u['name']===$a?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
        <span class="versus">VS</span>
        <select class="form-select" name="b"><?php foreach($universities as $u): ?><option value="<?= e($u['name']) ?>" <?= $u['name']===$b?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary">Compare</button>
    </form>
    <?php if($left && $right): ?>
    <section class="race-card card card-pad mb-3">
        <div class="center"><span class="eyebrow"><?= e($left['city'] ?? '') ?></span><h2><?= e($left['name']) ?></h2><div class="dna-score"><?= (int)$left['avg_rating'] ?><small> avg rating</small></div></div>
        <div class="versus">VS</div>
        <div class="center"><span class="eyebrow"><?= e($right['city'] ?? '') ?></span><h2><?= e($right['name']) ?></h2><div class="dna-score"><?= (int)$right['avg_rating'] ?><small> avg rating</small></div></div>
    </section>
    <section class="card table-wrap">
        <table class="data-table"><thead><tr><th>Metric</th><th><?= e($left['name']) ?></th><th><?= e($right['name']) ?></th><th>Edge</th></tr></thead><tbody>
        <?php
        $metrics = [
            'Students' => [(int)$left['students'], (int)$right['students']],
            'Accepted problem solves' => [(int)$left['total_solves'], (int)$right['total_solves']],
            'Average rating' => [(int)$left['avg_rating'], (int)$right['avg_rating']],
            'Top rating' => [(int)$left['top_rating'], (int)$right['top_rating']],
        ];
        foreach($metrics as $label=>$values): $winner=$values[0] <=> $values[1]; ?>
            <tr><td><strong><?= e($label) ?></strong></td><td><?= $values[0] ?></td><td><?= $values[1] ?></td><td class="<?= $winner===0?'muted':'text-cyan' ?>"><?= $winner>0?e($left['name']):($winner<0?e($right['name']):'Tie') ?></td></tr>
        <?php endforeach; ?>
        <tr><td><strong>Top coder</strong></td><td><?= $left['top_user'] ? e($left['top_user']['username']).' · '.(int)$left['top_user']['rating'] : '—' ?></td><td><?= $right['top_user'] ? e($right['top_user']['username']).' · '.(int)$right['top_user']['rating'] : '—' ?></td><td>—</td></tr>
        </tbody></table>
    </section>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
