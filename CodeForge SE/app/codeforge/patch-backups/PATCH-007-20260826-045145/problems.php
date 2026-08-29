<?php
require_once __DIR__.'/core/bootstrap.php';
$user=require_login($pdo);
require_once __DIR__.'/repositories/ProblemRepository.php';
$repo=new ProblemRepository($pdo);
$topic=trim((string)($_GET['topic']??''));$difficulty=trim((string)($_GET['difficulty']??''));$q=mb_substr(trim((string)($_GET['q']??'')),0,100);
$problems=$repo->all($topic?:null,$difficulty?:null,$q?:null);$topics=$repo->topics();
$solvedStmt=$pdo->prepare("SELECT DISTINCT problem_id FROM submissions WHERE user_id=:u AND verdict='AC'");$solvedStmt->execute(['u'=>$user['id']]);$solved=array_flip($solvedStmt->fetchAll(PDO::FETCH_COLUMN));
$pageTitle='Problems';include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><div class="eyebrow">Practice Library</div><h1>Problems</h1><p>Train across algorithms and build the performance history that powers Code DNA and Ghost Race.</p></div><div><span class="pill pill-neutral"><?=count($problems)?> problems</span></div></div>
<form class="card filter-bar" method="get"><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Search title or tag"><select class="form-select" name="topic"><option value="">All topics</option><?php foreach($topics as $t):?><option <?= $topic===$t?'selected':'' ?>><?=e($t)?></option><?php endforeach;?></select><select class="form-select" name="difficulty"><option value="">All difficulties</option><?php foreach(['Easy','Medium','Hard'] as $d):?><option <?= $difficulty===$d?'selected':'' ?>><?=$d?></option><?php endforeach;?></select><button class="btn btn-primary">Filter</button><a class="btn btn-secondary" href="problems.php">Reset</a></form>
<div class="card table-wrap"><table class="data-table"><thead><tr><th>Problem</th><th>Topic</th><th>Difficulty</th><th>Solved by</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($problems as $p):?><tr><td><strong><?=e($p['title'])?></strong><br><small class="muted mono"><?=e($p['id'])?></small></td><td><?=e($p['topic'])?></td><td><span class="pill <?=difficulty_class($p['difficulty'])?>"><?=e($p['difficulty'])?></span></td><td><?=(int)$p['solved_by']?></td><td><?php if(isset($solved[$p['id']])):?><span class="status-success"><i class="bi bi-check-circle"></i> Solved</span><?php else:?><span class="muted">Unsolved</span><?php endif;?></td><td class="right"><a class="btn btn-sm btn-secondary" href="solve.php?id=<?=e($p['id'])?>">Open <i class="bi bi-arrow-right"></i></a></td></tr><?php endforeach;?></tbody></table><?php if(!$problems):?><div class="empty"><i class="bi bi-search"></i>No matching problems.</div><?php endif;?></div>
<?php include __DIR__.'/includes/footer.php';?>
