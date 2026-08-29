<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/ProblemPracticeService.php';

$user = require_login($pdo);
$service = new ProblemPracticeService($pdo);
$problemId = trim((string)($_GET['id'] ?? $_POST['problem_id'] ?? ''));
$contestId = trim((string)($_GET['contest'] ?? $_POST['contest_id'] ?? ''));
$problem = $service->getProblem($problemId);
if (!$problem) { http_response_code(404); exit('Problem not found.'); }

$contest = null;
if ($contestId !== '') {
    $stmt = $pdo->prepare("SELECT c.id,c.name,c.status,cp.points,
            EXISTS(SELECT 1 FROM contest_participants cpa WHERE cpa.contest_id=c.id AND cpa.user_id=:uid) joined
        FROM contests c JOIN contest_problems cp ON cp.contest_id=c.id AND cp.problem_id=:pid
        WHERE c.id=:cid LIMIT 1");
    $stmt->execute(['uid'=>$user['id'],'pid'=>$problemId,'cid'=>$contestId]);
    $contest = $stmt->fetch();
    if (!$contest || !(bool)$contest['joined']) {
        flash('error', 'Join the contest before opening its problem workspace.');
        redirect('contest_view.php?id='.urlencode($contestId));
    }
}

if (isset($_GET['new']) && $_GET['new'] === '1') {
    $pdo->prepare("UPDATE problem_sessions ps
                   SET ps.status='abandoned', ps.completed_at=NOW()
                   WHERE ps.user_id=:uid
                     AND ps.problem_id=:pid
                     AND ps.status='active'
                     AND NOT EXISTS (
                         SELECT 1 FROM ghost_races gr
                         WHERE gr.challenger_session_id=ps.id AND gr.result='active'
                     )")
        ->execute(['uid'=>$user['id'],'pid'=>$problemId]);
    $url = 'solve.php?id='.urlencode($problemId);
    if ($contestId !== '') $url .= '&contest='.urlencode($contestId);
    redirect($url);
}

$sessionId = trim((string)($_GET['session'] ?? $_POST['session_id'] ?? ''));
if ($sessionId === '') {
    $sessionId = (string)$service->getOrCreateSession((string)$user['id'], $problemId)['id'];
    $url = 'solve.php?id='.urlencode($problemId).'&session='.urlencode($sessionId);
    if ($contestId !== '') $url .= '&contest='.urlencode($contestId);
    redirect($url);
}

$result = null;
$sourceCode = (string)($_POST['source_code'] ?? ($problem['starter_code'] ?? ''));
$language = (string)($_POST['language'] ?? 'C++');
if (request_method('POST')) {
    verify_csrf();
    try {
        $result = $service->submit((string)$user['id'], $sessionId, $problemId, $sourceCode, $language, $contestId ?: null);
    } catch (Throwable $error) {
        flash('error', user_safe_error($error, 'Could not process the submission.'));
        $url = 'solve.php?id='.urlencode($problemId).'&session='.urlencode($sessionId);
        if ($contestId !== '') $url .= '&contest='.urlencode($contestId);
        redirect($url);
    }
}
$attempts = $service->sessionAttempts($sessionId);
$pageTitle = $problem['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div><div class="eyebrow"><?= $contest ? 'Contest · '.e($contest['name']).' · '.(int)$contest['points'].' pts' : e($problem['topic']).' · '.e($problem['difficulty']) ?></div><h1><?= e($problem['title']) ?></h1><p><?= e($problem['description']) ?></p></div>
    <a class="btn btn-secondary" href="<?= $contest ? 'contest_view.php?id='.e($contestId) : 'problems.php' ?>"><i class="bi bi-arrow-left"></i><?= $contest ? 'Contest' : 'Library' ?></a>
</div>
<div class="grid grid-2">
    <div class="card card-pad">
        <div class="card-head"><h3>Problem brief</h3><span class="pill <?= difficulty_class($problem['difficulty']) ?>"><?= e($problem['difficulty']) ?></span></div>
        <p class="muted" style="line-height:1.75"><?= e($problem['description']) ?></p>
        <?php if($contest): ?><div class="callout"><strong>Contest mode:</strong> your first accepted submission for this problem adds <?= (int)$contest['points'] ?> points to the contest leaderboard.</div><?php endif; ?>
        <div class="callout"><strong>Prototype judge:</strong> this safe pre-framework version does not execute arbitrary code. It records deterministic verdicts so submission, analytics and Ghost Race workflows can be tested without exposing the computer to untrusted code.</div>
        <h3 class="section-title">Session attempts</h3>
        <div class="list">
            <?php foreach($attempts as $attempt): ?><div class="list-item"><div><strong class="<?= verdict_class($attempt['verdict']) ?>"><?= e($attempt['verdict']) ?></strong><br><small><?= e(format_duration((int)$attempt['elapsed_seconds'])) ?> · <?= e($attempt['language']) ?></small></div><span class="mono muted"><?= (int)$attempt['runtime_ms'] ?> ms</span></div><?php endforeach; ?>
            <?php if(!$attempts): ?><span class="muted">No submissions yet.</span><?php endif; ?>
        </div>
    </div>
    <div class="card card-pad">
        <div class="card-head"><h3>Code workspace</h3><span class="badge-dot text-green">Session active</span></div>
        <?php if($result): ?><div class="toast-banner <?= $result['verdict']==='AC'?'success':'danger' ?>"><strong><?= e($result['verdict']) ?></strong> · <?= e($result['feedback']) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="problem_id" value="<?= e($problemId) ?>"><input type="hidden" name="session_id" value="<?= e($sessionId) ?>"><input type="hidden" name="contest_id" value="<?= e($contestId) ?>">
            <label class="form-label">Language</label><select class="form-select" name="language" style="margin-bottom:12px"><?php foreach(['C++','Python','JavaScript','PHP'] as $option): ?><option <?= $language===$option?'selected':'' ?>><?= e($option) ?></option><?php endforeach; ?></select>
            <textarea class="code-editor form-control" name="source_code" data-tab-indent spellcheck="false" required><?= e($sourceCode) ?></textarea>
            <div class="hero-actions"><button class="btn btn-primary"><i class="bi bi-send"></i>Submit solution</button><a class="btn btn-secondary" href="solve.php?id=<?= e($problemId) ?>&new=1<?= $contestId!==''?'&contest='.e($contestId):'' ?>"><i class="bi bi-arrow-repeat"></i>Restart session</a></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
