<?php
require_once __DIR__ . '/core/bootstrap.php';
$user=require_login($pdo);
if(!request_method('POST'))redirect('contests.php');verify_csrf();
$rival=trim((string)($_POST['rival']??''));$problem=trim((string)($_POST['problem']??''));
if($rival===''||$rival===$user['id']||$problem===''){flash('error','Choose a valid rival and problem.');redirect('contests.php');}
$check=$pdo->prepare('SELECT username FROM users WHERE id=:id');$check->execute(['id'=>$rival]);$rivalUser=$check->fetch();$p=$pdo->prepare('SELECT title FROM problems WHERE id=:id');$p->execute(['id'=>$problem]);$prob=$p->fetch();if(!$rivalUser||!$prob){flash('error','Duel setup is invalid.');redirect('contests.php');}
$id=generate_id('duel');try{$pdo->beginTransaction();$pdo->prepare("INSERT INTO contests(id,name,type,starts_at,status,created_by) VALUES(:id,:name,'Duel',NOW(),'Active',:uid)")->execute(['id'=>$id,'name'=>$user['username'].' vs '.$rivalUser['username'],'uid'=>$user['id']]);$pdo->prepare('INSERT INTO contest_problems(contest_id,problem_id,points) VALUES(:cid,:pid,500)')->execute(['cid'=>$id,'pid'=>$problem]);$join=$pdo->prepare('INSERT INTO contest_participants(contest_id,user_id,score) VALUES(:cid,:uid,0)');$join->execute(['cid'=>$id,'uid'=>$user['id']]);$join->execute(['cid'=>$id,'uid'=>$rival]);$pdo->commit();redirect('contest_view.php?id='.urlencode($id));}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error','Could not create duel: '.$e->getMessage());redirect('contests.php');}
