<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/SqlBattleService.php';
require_once __DIR__ . '/repositories/UserRepository.php';

$user=require_login($pdo);
$service=new SqlBattleService($pdo);
$challenges=$service->challenges();
$selectedId=(string)($_GET['challenge']??($challenges[0]['id']??''));
$challenge=$service->challenge($selectedId);
$result=null;
$error=null;
$query=(string)($_POST['query']??'');

if(request_method('POST')){
verify_csrf();
$action=(string)($_POST['action']??'practice');
$selectedId=(string)($_POST['challenge_id']??$selectedId);
$challenge=$service->challenge($selectedId);
try{
if($action==='create_battle'){
$battleId=$service->createBattle($selectedId,(string)$user['id'],(string)($_POST['opponent']??''));
redirect('sql_battle_play.php?id='.urlencode($battleId));
}
if($action==='practice'){
$result=$service->submit($selectedId,(string)$user['id'],$query,null);
}
}
catch(Throwable $e){
$error=user_safe_error($e,'Could not process the SQL Arena request.');
}
}

$users=(new UserRepository($pdo))->all(false);
$recent=$service->recentBattles((string)$user['id']);
$leaderboard=$service->leaderboard();
$pageTitle='SQL Battle Arena';
include __DIR__.'/includes/header.php';
?>
<div class="page">
<div class="page-head">
<div>
<span class="eyebrow">Feature · secure DBMS competition</span>
<h1 class="page-title">⚔ SQL Battle Arena</h1>
<p class="page-subtitle">Solve deterministic SQL challenges inside a read-only sandbox. Correctness is judged by result-set comparison; speed and EXPLAIN-based efficiency add bonus points.</p>
</div>
</div>
<?php if($error): ?>
<div class="alert alert-error">
<?= e($error) ?>
</div>
<?php endif; ?>
<section class="grid grid-3 mb-3">
<div class="card card-pad">
<span class="eyebrow">Challenge library</span>
<div class="list mt-1">
<?php foreach($challenges as $c): ?>
<a class="list-item" href="sql_battle.php?challenge=<?= e($c['id']) ?>">
<div>
<strong>
<?= e($c['title']) ?>
</strong>
<div class="muted small">
<?= e($c['difficulty']) ?> · <?= (int)$c['accepted_attempts'] ?> accepts</div>
</div>
<span class="pill <?= difficulty_class($c['difficulty']) ?>">
<?= e($c['difficulty']) ?>
</span>
</a>
<?php endforeach; ?>
</div>
</div>
<div class="card card-pad span-2">
<?php if($challenge): ?>
<div class="toolbar" style="justify-content:space-between">
<div>
<span class="eyebrow">
<?= e($challenge['difficulty']) ?> challenge</span>
<h2>
<?= e($challenge['title']) ?>
</h2>
</div>
<span class="pill pill-neutral">Max <?= (int)$challenge['max_score'] ?> pts</span>
</div>
<p class="muted">
<?= e($challenge['description']) ?>
</p>
<div class="code-box mb-2">Sandbox tables: arena_users(user_id, username, university, rating)
arena_universities(name)
arena_problems(problem_id, title, topic, difficulty)
arena_submissions(submission_id, user_id, problem_id, verdict, runtime_ms)</div>
<form method="post" class="form-grid">
<?= csrf_field() ?>
<input type="hidden" name="action" value="practice">
<input type="hidden" name="challenge_id" value="<?= e($challenge['id']) ?>">
<div class="field full">
<label>SQL editor</label>
<textarea id="sql-practice-editor" class="textarea sql-editor" data-tab-indent name="query" placeholder="SELECT ..." required>
<?= e($query) ?>
</textarea>
</div>
<div class="field full">
<button class="btn btn-primary">Run & judge query</button>
</div>
</form>
<?php if($result): ?>
<div class="alert <?= $result['correct']?'alert-success':'alert-info' ?> mt-2">
<strong>
<?= e(strtoupper(str_replace('_',' ',$result['status']))) ?>
</strong> · Score <?= (int)$result['score'] ?> · Efficiency <?= (int)$result['efficiency_score'] ?>%<?= $result['execution_time_ms']!==null?' · '.e($result['execution_time_ms']).' ms':'' ?>
<br>
<?= e($result['feedback']) ?>
</div>
<?php if($result['rows']): ?>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<?php foreach(array_keys($result['rows'][0]) as $col): ?>
<th>
<?= e($col) ?>
</th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach($result['rows'] as $row): ?>
<tr>
<?php foreach($row as $v): ?>
<td>
<?= e($v) ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
</div>
</section>
<section class="grid grid-3 mb-3">
<div class="card card-pad span-2">
<span class="eyebrow">Create battle</span>
<h2>Challenge another coder</h2>
<p class="muted small">Battles are asynchronous in this plain-PHP MVP. Each player can submit until accepted; when both have an accepted run, the higher best score wins.</p>
<form method="post" class="form-grid">
<?= csrf_field() ?>
<input type="hidden" name="action" value="create_battle">
<div class="field">
<label>Challenge</label>
<select class="select" name="challenge_id">
<?php foreach($challenges as $c): ?>
<option value="<?= e($c['id']) ?>" <?= $selectedId===$c['id']?'selected':'' ?>>
<?= e($c['title']) ?> · <?= e($c['difficulty']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label>Opponent</label>
<select class="select" name="opponent">
<?php foreach($users as $u): if($u['id']===$user['id'])continue; ?>
<option value="<?= e($u['id']) ?>">
<?= e($u['username']) ?> · <?= (int)$u['rating'] ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="field full">
<button class="btn btn-success">Create SQL battle</button>
</div>
</form>
</div>
<div class="card card-pad">
<span class="eyebrow">Safety model</span>
<h3>Read-only by design</h3>
<div class="stat-row">
<span>SELECT / WITH only</span>
<span class="status-success">✓</span>
</div>
<div class="stat-row">
<span>Single statement</span>
<span class="status-success">✓</span>
</div>
<div class="stat-row">
<span>arena_* tables only</span>
<span class="status-success">✓</span>
</div>
<div class="stat-row">
<span>DROP / UPDATE / DELETE</span>
<span class="status-danger">blocked</span>
</div>
<div class="stat-row">
<span>OUTFILE / SLEEP</span>
<span class="status-danger">blocked</span>
</div>
</div>
</section>
<section class="grid grid-2">
<div class="card">
<div class="card-head">
<h3>Recent battles</h3>
</div>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Challenge</th>
<th>Match</th>
<th>Status</th>
<th>Winner</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php foreach($recent as $b): ?>
<tr>
<td>
<?= e($b['title']) ?>
</td>
<td>
<?= e($b['player1_name']) ?> vs <?= e($b['player2_name']) ?>
</td>
<td>
<?= e($b['status']) ?>
</td>
<td>
<?= e($b['winner_name']??'—') ?>
</td>
<td>
<a class="btn btn-ghost btn-sm" href="sql_battle_play.php?id=<?= e($b['id']) ?>">Open</a>
</td>
</tr>
<?php endforeach; ?>
<?php if(!$recent): ?>
<tr>
<td colspan="5" class="empty">No battles yet.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
<div class="card">
<div class="card-head">
<h3>SQL leaderboard</h3>
</div>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>#</th>
<th>User</th>
<th>Best</th>
<th>Accepted</th>
</tr>
</thead>
<tbody>
<?php foreach($leaderboard as $i=>$r): ?>
<tr>
<td>
<?= $i+1 ?>
</td>
<td class="strong">
<?= e($r['username']) ?>
</td>
<td>
<?= (int)$r['best_score'] ?>
</td>
<td>
<?= (int)$r['accepted_runs'] ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</section>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
