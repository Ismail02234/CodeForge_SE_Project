<?php
$user = current_user($pdo);
$pageTitle = $pageTitle ?? 'CodeForge';
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title><?= e($pageTitle) ?> · CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="dashboard.php"><span class="brand-mark">&lt;/&gt;</span><span>CodeForge</span></a>
        <div class="sidebar-section">Workspace</div>
        <nav class="sidebar-nav">
            <a class="<?= $current==='dashboard.php'?'active':'' ?>" href="dashboard.php"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            <a class="<?= in_array($current,['problems.php','solve.php'],true)?'active':'' ?>" href="problems.php"><i class="bi bi-braces"></i>Problems</a>
            <a class="<?= $current==='contests.php'?'active':'' ?>" href="contests.php"><i class="bi bi-trophy"></i>Contests</a>
            <a class="<?= $current==='rivalry.php'?'active':'' ?>" href="rivalry.php"><i class="bi bi-lightning-charge"></i>Rivalry</a>
            <a class="<?= $current==='university.php'?'active':'' ?>" href="university.php"><i class="bi bi-mortarboard"></i>Universities</a>
        </nav>
        <div class="sidebar-section">Intelligence Lab</div>
        <nav class="sidebar-nav">
            <a class="feature-link <?= $current==='code_dna.php'?'active':'' ?>" href="code_dna.php"><i class="bi bi-hexagon"></i>Code DNA <span>NEW</span></a>
            <a class="feature-link <?= in_array($current,['ghost_race.php','ghost_play.php'],true)?'active':'' ?>" href="ghost_race.php"><i class="bi bi-ghost"></i>Ghost Race <span>NEW</span></a>
            <a class="feature-link <?= in_array($current,['sql_battle.php','sql_battle_play.php'],true)?'active':'' ?>" href="sql_battle.php"><i class="bi bi-database-gear"></i>SQL Battle <span>NEW</span></a>
        </nav>
        <?php if ($user && $user['role']==='admin'): ?>
        <div class="sidebar-section">Admin</div>
        <nav class="sidebar-nav"><a class="<?= $current==='database.php'?'active':'' ?>" href="database.php"><i class="bi bi-shield-lock"></i>Data Console</a></nav>
        <?php endif; ?>
        <div class="sidebar-bottom">
            <a href="how_it_works.php"><i class="bi bi-diagram-3"></i> Architecture</a>
            <?php if ($user): ?><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a><?php endif; ?>
        </div>
    </aside>
    <main class="main-panel">
        <header class="topbar">
            <button class="icon-btn mobile-only" id="menuToggle" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
            <form class="global-search" action="search.php" method="get"><i class="bi bi-search"></i><input name="q" placeholder="Search users, problems, universities…" autocomplete="off"></form>
            <div class="topbar-actions">
                <?php if ($user): ?>
                <div class="rating-chip"><i class="bi bi-star-fill"></i><?= (int)$user['rating'] ?></div>
                <a class="profile-chip" href="profile.php?id=<?= e($user['id']) ?>"><span class="avatar"><?= e(strtoupper(substr($user['username'],0,1))) ?></span><span><strong><?= e($user['username']) ?></strong><small><?= e($user['rank']) ?></small></span></a>
                <?php endif; ?>
            </div>
        </header>
        <div class="page-wrap">
            <?php if ($m=flash('success')): ?><div class="toast-banner success"><i class="bi bi-check-circle"></i><?= e($m) ?></div><?php endif; ?>
            <?php if ($m=flash('error')): ?><div class="toast-banner danger"><i class="bi bi-exclamation-triangle"></i><?= e($m) ?></div><?php endif; ?>
