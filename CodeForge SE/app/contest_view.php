<?php
require_once __DIR__ . '/core/bootstrap.php';

$user = require_login($pdo);

$id = trim((string)($_GET['id'] ?? $_POST['contest_id'] ?? ''));

$stmt = $pdo->prepare("SELECT c.*,u.username creator FROM contests c LEFT JOIN users u ON u.id=c.created_by WHERE c.id=:id");

$stmt->execute(['id'=>$id]);

$contest = $stmt->fetch();

if (!$contest) {
http_response_code(404);
exit('Contest not found.');
}

// start scheduled contests automatically
if ($contest['status'] === 'Upcoming' && strtotime((string)$contest['starts_at']) <= time()) {

    $pdo->prepare("UPDATE contests SET status='Active' WHERE id=:id AND status='Upcoming'")->execute(['id'=>$id]);

    $contest['status'] = 'Active';

}

if (request_method('POST')) {

    verify_csrf();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'join') {

        if ($contest['status'] === 'Past') {

            flash('error', 'This contest has already closed.');


}
else {

            $join = $pdo->prepare('INSERT IGNORE INTO contest_participants(contest_id,user_id,score) VALUES(:cid,:uid,0)');

            $join->execute(['cid'=>$id,'uid'=>$user['id']]);

            flash('success', 'You joined the contest.');


}

        redirect('contest_view.php?id='.urlencode($id));


}

    if ($action === 'close') {

        if ($user['role'] !== 'admin') {
flash('error','Administrator privileges are required.');
redirect('contest_view.php?id='.urlencode($id));
}

        $pdo->prepare("UPDATE contests SET status='Past' WHERE id=:id")->execute(['id'=>$id]);

        flash('success','Contest closed.');

        redirect('contest_view.php?id='.urlencode($id));


}

}

$joinedStmt = $pdo->prepare('SELECT 1 FROM contest_participants WHERE contest_id=:cid AND user_id=:uid LIMIT 1');

$joinedStmt->execute(['cid'=>$id,'uid'=>$user['id']]);

$joined = (bool)$joinedStmt->fetchColumn();

$p = $pdo->prepare("SELECT p.*,cp.points FROM contest_problems cp JOIN problems p ON p.id=cp.problem_id WHERE cp.contest_id=:id ORDER BY cp.points,p.title");

$p->execute(['id'=>$id]);

$problems = $p->fetchAll();

$l = $pdo->prepare("SELECT cp.score,u.id,u.username,u.rating FROM contest_participants cp JOIN users u ON u.id=cp.user_id WHERE cp.contest_id=:id ORDER BY cp.score DESC,u.rating DESC,u.username");

$l->execute(['id'=>$id]);

$board = $l->fetchAll();

$pageTitle = $contest['name'];

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
<div>
<div class="eyebrow">
<?= e($contest['type']) ?> Contest</div>
<h1>
<?= e($contest['name']) ?>
</h1>
<p>
<?= e($contest['starts_at']) ?> UTC · Created by <?= e($contest['creator']??'System') ?>
</p>
</div>
<div class="toolbar">
<span class="pill <?= $contest['status']==='Active'?'pill-easy':($contest['status']==='Upcoming'?'pill-medium':'pill-neutral') ?>">
<?= e($contest['status']) ?>
</span>
<?php if(!$joined && $contest['status']!=='Past'): ?>
<form method="post">
<?= csrf_field() ?>
<input type="hidden" name="contest_id" value="<?= e($id) ?>">
<input type="hidden" name="action" value="join">
<button class="btn btn-primary">Join contest</button>
</form>
<?php endif; ?>
<?php if($joined): ?>
<span class="status-success">
<i class="bi bi-check-circle">
</i> Joined</span>
<?php endif; ?>
<?php if($user['role']==='admin' && $contest['status']!=='Past'): ?>
<form method="post" onsubmit="return confirm('Close this contest?')">
<?= csrf_field() ?>
<input type="hidden" name="contest_id" value="<?= e($id) ?>">
<input type="hidden" name="action" value="close">
<button class="btn btn-danger btn-sm">Close</button>
</form>
<?php endif; ?>
</div>
</div>
<div class="grid grid-2">
    <div class="card card-pad">
<div class="card-head">
<h3>Problem set</h3>
<span class="pill pill-neutral">
<?= count($problems) ?> problems</span>
</div>
<div class="list">
<?php foreach($problems as $x): ?>
<div class="list-item">
<div>
<strong>
<?= e($x['title']) ?>
</strong>
<br>
<small>
<?= e($x['topic']) ?> · <?= e($x['difficulty']) ?>
</small>
</div>
<div class="toolbar">
<span class="mono text-cyan">
<?= (int)$x['points'] ?> pts</span>
<?php if($joined && $contest['status']==='Active'): ?>
<a class="btn btn-sm btn-secondary" href="solve.php?id=<?= e($x['id']) ?>&contest=<?= e($id) ?>">Compete</a>
<?php elseif($contest['status']==='Past'): ?>
<a class="btn btn-sm btn-secondary" href="solve.php?id=<?= e($x['id']) ?>">Practice</a>
<?php else: ?>
<span class="muted small">
<?= $joined?'Opens at start':'Join first' ?>
</span>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
    <div class="card card-pad">
<div class="card-head">
<h3>Leaderboard</h3>
<span class="pill pill-neutral">
<?= count($board) ?> participants</span>
</div>
<div class="list">
<?php foreach($board as $i=>$x): ?>
<div class="list-item">
<div>
<strong>#<?= $i+1 ?>
<a href="profile.php?id=<?= e($x['id']) ?>">
<?= e($x['username']) ?>
</a>
</strong>
<br>
<small>Rating <?= (int)$x['rating'] ?>
</small>
</div>
<span class="mono text-green">
<?= (int)$x['score'] ?>
</span>
</div>
<?php endforeach; ?>
<?php if(!$board): ?>
<div class="empty">No participants yet.</div>
<?php endif; ?>
</div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
