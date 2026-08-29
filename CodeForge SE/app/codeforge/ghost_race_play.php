<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/services/GhostRaceService.php';

$user = require_login($pdo);
$service = new GhostRaceService($pdo);
$raceId = trim((string)($_GET['id'] ?? $_POST['race_id'] ?? ''));
$race = $service->getRace($raceId, (string)$user['id']);

if (!$race) {
    http_response_code(404);
    exit('Ghost Race not found.');
}

$result = null;
$error = null;
$sourceCode = (string)($_POST['source_code'] ?? ($race['starter_code'] ?? ''));
$language = (string)($_POST['language'] ?? 'C++');

if (request_method('POST')) {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'submit');
    try {
        if ($action === 'forfeit') {
            $service->forfeit($raceId, (string)$user['id']);
            flash('error', 'Race forfeited. You can start another ghost run whenever you are ready.');
            redirect('ghost_race.php');
        }
        $result = $service->submit($raceId, (string)$user['id'], $sourceCode, $language);
        $race = $service->getRace($raceId, (string)$user['id']) ?? $race;
    } catch (Throwable $exception) {
        $error = user_safe_error($exception, 'Could not update the Ghost Race.');
    }
}

$ghostEvents = $service->ghostEvents((string)$race['ghost_session_id']);
$myEvents = $service->challengerEvents((string)$race['challenger_session_id']);
$virtualElapsed = $service->virtualElapsed($race);
$startedMs = max(0, (int)(strtotime((string)$race['started_at']) * 1000));
$pageTitle = 'Ghost Race · ' . $race['problem_title'];
$pageScripts = ['assets/js/ghost-race.js'];
include __DIR__ . '/includes/header.php';

$resultLabel = match ((string)$race['result']) {
    'won' => 'You beat the ghost',
    'lost' => 'The ghost wins this round',
    'draw' => 'Perfect tie',
    'forfeit' => 'Race forfeited',
    default => 'Race in progress',
};
$resultClass = match ((string)$race['result']) {
    'won' => 'status-success',
    'lost', 'forfeit' => 'status-danger',
    'draw' => 'status-warning',
    default => 'status-info',
};
?>
<div class="page" data-race-root data-started-ms="<?= $startedMs ?>" data-speed="<?= (int)$race['playback_speed'] ?>" data-ghost-time="<?= (int)$race['ghost_time'] ?>">
    <div class="page-head">
        <div>
            <span class="eyebrow">Ghost Race · <?= e($race['topic']) ?> · <?= e($race['difficulty']) ?></span>
            <h1 class="page-title"><?= e($race['problem_title']) ?></h1>
            <p class="page-subtitle">Beat <?= e($race['ghost_username']) ?>'s historical accepted time while their old submission events replay alongside you.</p>
        </div>
        <a class="btn btn-secondary" href="ghost_race.php"><i class="bi bi-arrow-left"></i>Race lobby</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
        <div class="alert <?= $result['verdict'] === 'AC' ? 'alert-success' : 'alert-info' ?>">
            <strong><?= e($result['verdict']) ?></strong> · <?= e($result['feedback']) ?> · submitted at <?= e(format_duration((int)$result['elapsed_seconds'])) ?> race time.
        </div>
    <?php endif; ?>

    <section class="race-stage card mb-3">
        <div class="race-player">
            <span class="eyebrow">You</span>
            <h2><?= e($race['challenger_username']) ?></h2>
            <div class="dna-score"><?= count($myEvents) ?><small> attempts</small></div>
            <div class="ghost-status">Latest: <?= $myEvents ? e(end($myEvents)['verdict']) : 'Coding…' ?></div>
        </div>
        <div class="race-vs">
            <div class="vs"><?= (int)$race['playback_speed'] ?>× playback</div>
            <div class="timer" data-race-timer><?= e(format_duration($virtualElapsed)) ?></div>
            <span class="pill <?= $race['result'] === 'active' ? 'pill-medium' : 'pill-neutral' ?>"><?= e(strtoupper((string)$race['result'])) ?></span>
        </div>
        <div class="race-player">
            <span class="eyebrow">Ghost · <?= e($race['ghost_username']) ?></span>
            <h2><?= e(format_duration((int)$race['ghost_time'])) ?></h2>
            <div class="dna-score"><?= count($ghostEvents) ?><small> attempts</small></div>
            <div class="ghost-status" data-ghost-state><?= $virtualElapsed >= (int)$race['ghost_time'] ? 'Ghost finished' : 'Ghost coding…' ?></div>
        </div>
    </section>

    <?php if ($race['result'] !== 'active'): ?>
        <section class="card race-result mb-3">
            <span class="eyebrow">Final result</span>
            <h2 class="<?= $resultClass ?>"><?= e($resultLabel) ?></h2>
            <p class="muted">
                Your time: <?= $race['challenger_time'] !== null ? e(format_duration((int)$race['challenger_time'])) : '—' ?> ·
                Ghost time: <?= e(format_duration((int)$race['ghost_time'])) ?> ·
                Attempts: <?= count($myEvents) ?> vs <?= count($ghostEvents) ?>
            </p>
            <a class="btn btn-primary" href="ghost_race.php">Race another ghost</a>
        </section>
    <?php endif; ?>

    <section class="grid grid-2">
        <div class="card card-pad">
            <div class="card-head"><h3>Ghost timeline</h3><span class="pill pill-neutral">No source code exposed</span></div>
            <div class="event-timeline">
                <div class="event is-past"><div class="event-time">00:00</div><div class="event-body"><strong>STARTED</strong><br><span class="muted">Ghost opened the problem.</span></div></div>
                <?php foreach ($ghostEvents as $event): ?>
                    <?php $at = (int)($event['elapsed_seconds'] ?? 0); ?>
                    <div class="event <?= $virtualElapsed >= $at ? 'is-past' : '' ?>" data-ghost-event data-at="<?= $at ?>">
                        <div class="event-time"><?= e(format_duration($at)) ?></div>
                        <div class="event-body"><strong class="<?= verdict_class((string)$event['verdict']) ?>"><?= e((string)$event['verdict']) ?></strong><br><span class="muted"><?= e((string)$event['language']) ?> · <?= (int)$event['runtime_ms'] ?> ms</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card card-pad">
            <div class="card-head"><h3>Your timeline</h3><span class="pill pill-neutral"><?= count($myEvents) ?> attempts</span></div>
            <div class="event-timeline">
                <div class="event is-past"><div class="event-time">00:00</div><div class="event-body"><strong>STARTED</strong><br><span class="muted">Your challenger session began.</span></div></div>
                <?php foreach ($myEvents as $event): ?>
                    <div class="event is-past">
                        <div class="event-time"><?= e(format_duration((int)($event['elapsed_seconds'] ?? 0))) ?></div>
                        <div class="event-body"><strong class="<?= verdict_class((string)$event['verdict']) ?>"><?= e((string)$event['verdict']) ?></strong><br><span class="muted"><?= e((string)$event['language']) ?> · <?= (int)$event['runtime_ms'] ?> ms</span></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$myEvents): ?><div class="empty">Your first submission will appear here.</div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="grid grid-2 mt-3">
        <div class="card card-pad">
            <span class="eyebrow">Problem brief</span>
            <h2><?= e($race['problem_title']) ?></h2>
            <p class="muted" style="line-height:1.7"><?= e($race['description']) ?></p>
            <div class="callout"><strong>Prototype judge:</strong> source code is never executed on your machine. This safe pre-framework build records deterministic verdicts so the Ghost Race workflow can be demonstrated reliably.</div>
        </div>
        <div class="card card-pad">
            <div class="card-head"><h3>Race workspace</h3><span class="badge-dot text-green"><?= $race['result'] === 'active' ? 'Active' : 'Finished' ?></span></div>
            <?php if ($race['result'] === 'active'): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="race_id" value="<?= e($raceId) ?>">
                    <input type="hidden" name="action" value="submit">
                    <label class="form-label">Language</label>
                    <select class="form-select" name="language" style="margin-bottom:12px">
                        <?php foreach (['C++','Python','JavaScript','PHP'] as $option): ?><option <?= $language === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
                    </select>
                    <textarea class="code-editor form-control" name="source_code" data-tab-indent spellcheck="false" required><?= e($sourceCode) ?></textarea>
                    <div class="hero-actions">
                        <button class="btn btn-primary"><i class="bi bi-send"></i>Submit in race</button>
                    </div>
                </form>
                <form method="post" class="mt-2" onsubmit="return confirm('Forfeit this race?')">
                    <?= csrf_field() ?><input type="hidden" name="race_id" value="<?= e($raceId) ?>"><input type="hidden" name="action" value="forfeit">
                    <button class="btn btn-danger btn-sm" type="submit">Forfeit race</button>
                </form>
            <?php else: ?>
                <div class="empty">This race has finished. Start a new race from the lobby to compete again.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
