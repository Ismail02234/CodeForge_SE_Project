<?php
require_once __DIR__ . '/core/bootstrap.php';

require_once __DIR__ . '/services/CodeDnaService.php';

require_once __DIR__ . '/repositories/UserRepository.php';

$viewer=require_login($pdo);
$repo=new UserRepository($pdo);
$users=$repo->all(false);

$selected=(string)($_GET['user']??$viewer['id']);
$compare=(string)($_GET['compare']??'');

$service=new CodeDnaService($pdo);
try{
$dna=$service->calculate($selected);
}
catch(Throwable){
$selected=(string)$viewer['id'];
$dna=$service->calculate($selected);
}

$compareDna=null;
if($compare!==''&&$compare!==$selected){
try{
$compareDna=$service->calculate($compare);
}
catch(Throwable){
$compareDna=null;
}
}

$pageTitle='Code DNA';
$pageScripts=['assets/js/code-dna.js'];
include __DIR__.'/includes/header.php';

$labels=array_values($dna['dimension_labels']);
$values=array_values($dna['dimensions']);
?>
<div class="page">
 <div class="page-head">
<div>
<span class="eyebrow">Feature · explainable analytics</span>
<h1 class="page-title">🧬 Code DNA</h1>
<p class="page-subtitle">A reproducible programming fingerprint derived from submission accuracy, normalized solve speed, difficulty, recency, consistency and topic breadth.</p>
</div>
</div>
 <form class="card card-pad mb-3" method="get">
<div class="form-grid">
<div class="field">
<label>Analyze user</label>
<select class="select" name="user">
<?php foreach($users as $u): ?>
<option value="<?= e($u['id']) ?>" <?= $selected===$u['id']?'selected':'' ?>>
<?= e($u['username']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="field">
<label>Compare with (optional)</label>
<select class="select" name="compare">
<option value="">No comparison</option>
<?php foreach($users as $u): if($u['id']===$selected)continue; ?>
<option value="<?= e($u['id']) ?>" <?= $compare===$u['id']?'selected':'' ?>>
<?= e($u['username']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="field full">
<button class="btn btn-primary">Generate DNA</button>
</div>
</div>
</form>
 <section class="grid grid-3 mb-3">
  <div class="card card-pad">
<span class="eyebrow">Overall DNA</span>
<div class="dna-score mt-1">
<?= (int)$dna['overall'] ?>
<small>/100</small>
</div>
<div class="archetype mt-2">
<strong>
<?= e($dna['archetype']['name']) ?>
</strong>
<p class="muted small mb-0">
<?= e($dna['archetype']['tagline']) ?>
</p>
</div>
<div class="stat-row mt-2">
<span class="muted">Submissions</span>
<strong>
<?= (int)$dna['stats']['total_submissions'] ?>
</strong>
</div>
<div class="stat-row">
<span class="muted">Solved problems</span>
<strong>
<?= (int)$dna['stats']['solved_problems'] ?>
</strong>
</div>
<div class="stat-row">
<span class="muted">Solved topics</span>
<strong>
<?= (int)$dna['stats']['solved_topics'] ?>
</strong>
</div>
</div>
  <div class="card span-2">
<div class="card-head">
<h3>
<?= e($dna['user']['username']) ?> · DNA radar</h3>
<span class="pill pill-neutral">Rating <?= (int)$dna['user']['rating'] ?>
</span>
</div>
<div class="radar-wrap">
<canvas data-dna-radar data-values='<?= e(json_encode($values)) ?>' data-labels='<?= e(json_encode($labels)) ?>'>
</canvas>
</div>
</div>
 </section>
 <section class="grid grid-3 mb-3">
  <?php foreach($dna['dimensions'] as $key=>$value): ?>
<div class="card metric">
<div class="label">
<?= e($dna['dimension_labels'][$key]) ?>
</div>
<div class="value">
<?= (int)$value ?>
<span class="muted" style="font-size:18px">%</span>
</div>
<div class="progress">
<span style="width:<?= (int)$value ?>%">
</span>
</div>
</div>
<?php endforeach; ?>
 </section>
 <section class="grid grid-3 mb-3">
  <div class="card card-pad span-2">
<span class="eyebrow">Topic genome</span>
<h2>Skill map</h2>
<?php foreach($dna['topics'] as $topic=>$row): ?>
<div class="topic-bar">
<span class="name">
<?= e($topic) ?>
</span>
<div class="progress">
<span style="width:<?= (int)$row['score'] ?>%">
</span>
</div>
<strong>
<?= (int)$row['score'] ?>
</strong>
</div>
<?php endforeach; ?>
</div>
  <div class="card card-pad">
<span class="eyebrow">Interpretation</span>
<h3>Strengths</h3>
<?php foreach($dna['strengths'] as $s): ?>
<div class="stat-row">
<span>✓ <?= e($s) ?>
</span>
<span class="status-success">strong</span>
</div>
<?php endforeach; ?>
<h3 class="mt-3">Growth areas</h3>
<?php foreach($dna['growth_areas'] as $s): ?>
<div class="stat-row">
<span>↗ <?= e($s) ?>
</span>
<span class="status-warning">focus</span>
</div>
<?php endforeach; ?>
<a class="btn btn-secondary btn-block mt-2" href="how_it_works.php#dna">See scoring formula</a>
</div>
 </section>
 <?php if($compareDna): ?>
<section class="card">
<div class="card-head">
<h3>DNA comparison</h3>
<span class="pill pill-neutral">
<?= e($dna['user']['username']) ?> vs <?= e($compareDna['user']['username']) ?>
</span>
</div>
<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Dimension</th>
<th>
<?= e($dna['user']['username']) ?>
</th>
<th>
<?= e($compareDna['user']['username']) ?>
</th>
<th>Difference</th>
</tr>
</thead>
<tbody>
<?php foreach($dna['dimensions'] as $key=>$value): $other=(int)$compareDna['dimensions'][$key];$diff=(int)$value-$other; ?>
<tr>
<td class="strong">
<?= e($dna['dimension_labels'][$key]) ?>
</td>
<td>
<?= (int)$value ?>%</td>
<td>
<?= $other ?>%</td>
<td class="<?= $diff>=0?'kpi-positive':'kpi-negative' ?>">
<?= $diff>0?'+':'' ?>
<?= $diff ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
<?php endif; ?>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
