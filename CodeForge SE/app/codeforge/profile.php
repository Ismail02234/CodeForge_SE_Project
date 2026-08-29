<?php
require_once __DIR__.'/core/bootstrap.php';
$me=require_login($pdo);
require_once __DIR__.'/repositories/UserRepository.php';
require_once __DIR__.'/services/CodeDnaService.php';
$id=(string)($_GET['id']??$me['id']);
$u=(new UserRepository($pdo))->find($id);
if(!$u){
http_response_code(404);
exit('User not found');
}
$dna=(new CodeDnaService($pdo))->calculate($id);
$s=$pdo->prepare("SELECT p.title,p.difficulty,s.verdict,s.submitted_at FROM submissions s JOIN problems p ON p.id=s.problem_id WHERE s.user_id=:u ORDER BY s.submitted_at DESC LIMIT 10");
$s->execute(['u'=>$id]);
$recent=$s->fetchAll();
$pageTitle=$u['username'];
include __DIR__.'/includes/header.php';
?>
<section class="hero">
<div class="hero-grid">
<div>
<div class="eyebrow">Coder Profile</div>
<h1>
<?=e($u['username'])?>
</h1>
<p>
<?=e($u['university'])?> · <?=e($u['rank'])?>
</p>
<div class="hero-actions">
<a class="btn btn-primary" href="code_dna.php?user=<?=e($u['id'])?>">
<i class="bi bi-hexagon">
</i>Inspect Code DNA</a>
<a class="btn btn-secondary" href="rivalry.php?a=<?=e($me['id'])?>&b=<?=e($u['id'])?>">Compare</a>
</div>
</div>
<div class="grid grid-2">
<div class="card stat">
<div class="label">Rating</div>
<div class="value cyan">
<?=(int)$u['rating']?>
</div>
</div>
<div class="card stat">
<div class="label">DNA</div>
<div class="value purple">
<?=$dna['overall']?>%</div>
</div>
<div class="card stat">
<div class="label">Solved</div>
<div class="value green">
<?=(int)$u['solved_count']?>
</div>
</div>
<div class="card stat">
<div class="label">Submissions</div>
<div class="value">
<?=(int)$u['submission_count']?>
</div>
</div>
</div>
</div>
</section>
<div class="card table-wrap">
<table class="data-table">
<thead>
<tr>
<th>Recent problem</th>
<th>Difficulty</th>
<th>Verdict</th>
<th>Submitted</th>
</tr>
</thead>
<tbody>
<?php foreach($recent as $x):?>
<tr>
<td>
<?=e($x['title'])?>
</td>
<td>
<span class="pill <?=difficulty_class($x['difficulty'])?>">
<?=e($x['difficulty'])?>
</span>
</td>
<td class="<?=verdict_class($x['verdict'])?>">
<?=e($x['verdict'])?>
</td>
<td>
<?=e($x['submitted_at'])?>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
<?php include __DIR__.'/includes/footer.php';?>
