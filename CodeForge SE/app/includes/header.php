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
    <meta name="theme-color" content="#050506">
    <meta name="description" content="CodeForge competitive programming intelligence platform">
    <title><?= e($pageTitle) ?> · CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/aggressive.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/polish.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Primary navigation">
        <a class="brand" href="dashboard.php"><span class="brand-mark">&lt;/&gt;</span><span>CodeForge</span></a>
        <div class="sidebar-section">Workspace</div>
        <nav class="sidebar-nav">
            <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="bi bi-grid-1x2"></i>Dashboard</a>
            <a class="<?= in_array($current, ['problems.php', 'solve.php'], true) ? 'active' : '' ?>" href="problems.php"><i class="bi bi-braces"></i>Problems</a>
            <a class="<?= in_array($current, ['contests.php', 'contest_view.php'], true) ? 'active' : '' ?>" href="contests.php"><i class="bi bi-trophy"></i>Contests</a>
            <a class="<?= $current === 'rivalry.php' ? 'active' : '' ?>" href="rivalry.php"><i class="bi bi-lightning-charge"></i>Rivalry</a>
            <a class="<?= in_array($current, ['university.php', 'university_compare.php'], true) ? 'active' : '' ?>" href="university.php"><i class="bi bi-mortarboard"></i>Universities</a>
            <a class="<?= in_array($current, ['database.php', 'edit_user.php'], true) ? 'active' : '' ?>" href="database.php"><i class="bi bi-table"></i>Database</a>
        </nav>

        <div class="sidebar-section">Intelligence Lab</div>
        <nav class="sidebar-nav">
            <a class="feature-link <?= $current === 'code_dna.php' ? 'active' : '' ?>" href="code_dna.php"><i class="bi bi-hexagon"></i>Code DNA</a>
            <a class="feature-link <?= $current === 'progress.php' ? 'active' : '' ?>" href="progress.php#gamification"><i class="bi bi-stars"></i>Gamification <span>NEW</span></a>
            <a class="feature-link <?= $current === 'progress.php' ? 'active' : '' ?>" href="progress.php#quest-advisor"><i class="bi bi-compass"></i>Quest Advisor <span>NEW</span></a>
            <a class="feature-link <?= $current === 'progress.php' ? 'active' : '' ?>" href="progress.php#skill-tree"><i class="bi bi-diagram-3"></i>Skill Tree <span>NEW</span></a>
            <a class="feature-link <?= in_array($current, ['ghost_race.php', 'ghost_race_play.php'], true) ? 'active' : '' ?>" href="ghost_race.php"><i class="bi bi-ghost"></i>Ghost Race</a>
            <a class="feature-link <?= in_array($current, ['sql_battle.php', 'sql_battle_play.php'], true) ? 'active' : '' ?>" href="sql_battle.php"><i class="bi bi-database-gear"></i>SQL Battle</a>
        </nav>

        <div class="sidebar-section">Discover</div>
        <nav class="sidebar-nav">
            <a class="<?= $current === 'search.php' ? 'active' : '' ?>" href="search.php"><i class="bi bi-search"></i>Global Search</a>
            <?php if ($user): ?><a class="<?= $current === 'profile.php' ? 'active' : '' ?>" href="profile.php?id=<?= e($user['id']) ?>"><i class="bi bi-person-badge"></i>My Profile</a><?php endif; ?>
            <a class="<?= $current === 'how_it_works.php' ? 'active' : '' ?>" href="how_it_works.php"><i class="bi bi-diagram-3"></i>How It Works</a>
        </nav>

        <?php if ($user && $user['role'] === 'admin'): ?>
        <div class="sidebar-section">Admin</div>
        <nav class="sidebar-nav">
            <a class="<?= $current === 'sql_lab.php' ? 'active' : '' ?>" href="sql_lab.php"><i class="bi bi-terminal"></i>SQL Lab</a>
        </nav>
        <?php endif; ?>

        <div class="sidebar-bottom">
            <a href="index.php"><i class="bi bi-house"></i> Landing</a>
            <?php if ($user): ?><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a><?php endif; ?>
        </div>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="icon-btn mobile-only" id="menuToggle" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false"><i class="bi bi-list"></i></button>
            <form class="global-search" action="search.php" method="get" role="search">
                <i class="bi bi-search"></i>
                <input id="globalSearchInput" name="q" placeholder="Search users, problems, universities…" autocomplete="off" maxlength="100">
                <kbd class="search-shortcut">Ctrl K</kbd>
            </form>
            <div class="topbar-actions">
                <?php if ($user): ?>
                <div class="rating-chip" title="Current rating"><i class="bi bi-star-fill"></i><?= (int) $user['rating'] ?></div>
                <a class="profile-chip" href="profile.php?id=<?= e($user['id']) ?>"><span class="avatar"><?= e(strtoupper(substr($user['username'], 0, 1))) ?></span><span><strong><?= e($user['username']) ?></strong><small><?= e($user['rank']) ?></small></span></a>
                <a class="topbar-logout" href="logout.php" title="Log out" aria-label="Log out"><i class="bi bi-box-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </header>
        <div class="page-wrap">
            <?php if ($message = flash('success')): ?><div class="toast-banner success" role="status"><i class="bi bi-check-circle"></i><?= e($message) ?></div><?php endif; ?>
            <?php if ($message = flash('error')): ?><div class="toast-banner danger" role="alert"><i class="bi bi-exclamation-triangle"></i><?= e($message) ?></div><?php endif; ?>
