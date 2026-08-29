<?php
require_once __DIR__ . '/core/bootstrap.php';

$user = current_user($pdo);

$stats = [
    'users' => 0,
    'problems' => 0,
    'submissions' => 0,
];

try {
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['problems'] = (int) $pdo->query('SELECT COUNT(*) FROM problems')->fetchColumn();
    $stats['submissions'] = (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
} catch (Throwable $e) {
    // Landing page must remain available even if a statistic cannot be loaded.
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <meta name="color-scheme" content="dark">
    <title>CodeForge — Forge Your Edge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="forge-landing">
<canvas id="forgeCanvas" aria-hidden="true"></canvas>
<div class="cursor-glow" id="cursorGlow" aria-hidden="true"></div>
<div class="noise" aria-hidden="true"></div>

<header class="landing-nav">
    <a href="index.php" class="landing-brand" aria-label="CodeForge home">
        <span class="brand-slash">&lt;/&gt;</span>
        <span>CODE<span>FORGE</span></span>
    </a>

    <nav class="landing-links" aria-label="Landing navigation">
        <a href="#system">System</a>
        <a href="#features">Features</a>
        <a href="#arena">Arena</a>
    </nav>

    <div class="landing-actions">
        <?php if ($user): ?>
            <span class="signed-chip"><i class="bi bi-circle-fill"></i><?= e($user['username']) ?></span>
            <a class="nav-btn nav-btn-ghost magnetic" href="dashboard.php">Dashboard</a>
        <?php else: ?>
            <a class="nav-btn nav-btn-ghost magnetic" href="login.php">Log in</a>
            <a class="nav-btn nav-btn-hot magnetic" href="register.php">Create account</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <section class="landing-hero" id="system">
        <div class="hero-copy" data-parallax="0.018">
            <div class="system-tag">
                <span class="pulse-dot"></span>
                COMPETITIVE PROGRAMMING // INTELLIGENCE SYSTEM
            </div>

            <h1>
                FORGE<br>
                <span class="cut-text">YOUR EDGE.</span>
            </h1>

            <p class="hero-lead">
                Train harder. Read your coding DNA. Race the ghosts of real solving sessions.
                Fight SQL battles. Turn every submission into an advantage.
            </p>

            <div class="hero-cta">
                <?php if ($user): ?>
                    <a href="dashboard.php" class="cta-primary magnetic">
                        ENTER COMMAND CENTER
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="logout.php" class="cta-secondary magnetic">SIGN OUT</a>
                <?php else: ?>
                    <a href="register.php" class="cta-primary magnetic">
                        CREATE YOUR ACCOUNT
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="login.php" class="cta-secondary magnetic">
                        LOG IN
                        <i class="bi bi-terminal"></i>
                    </a>
                <?php endif; ?>
            </div>

            <div class="hero-meta">
                <span><b>01</b> ANALYZE</span>
                <span><b>02</b> COMPETE</span>
                <span><b>03</b> EVOLVE</span>
            </div>
        </div>

        <div class="forge-visual" data-parallax="-0.024">
            <div class="forge-halo halo-a"></div>
            <div class="forge-halo halo-b"></div>
            <div class="forge-halo halo-c"></div>

            <div class="forge-core" id="forgeCore">
                <div class="core-grid"></div>
                <div class="core-ring core-ring-a"></div>
                <div class="core-ring core-ring-b"></div>
                <div class="core-center">
                    <span class="core-code">&lt;/&gt;</span>
                    <small>FORGE CORE</small>
                    <strong>ONLINE</strong>
                </div>
            </div>

            <div class="float-card float-card-a">
                <small>CODE DNA</small>
                <strong>87<span>%</span></strong>
                <div class="micro-bars">
                    <i style="--h:42%"></i><i style="--h:63%"></i><i style="--h:52%"></i><i style="--h:84%"></i><i style="--h:70%"></i>
                </div>
            </div>

            <div class="float-card float-card-b">
                <small>GHOST STATUS</small>
                <strong class="danger">-00:42</strong>
                <span>YOU ARE AHEAD</span>
            </div>

            <div class="float-card float-card-c">
                <small>SQL BATTLE</small>
                <strong>920</strong>
                <span>ARENA SCORE</span>
            </div>
        </div>

        <div class="hero-index">CF // 2.0</div>
    </section>

    <section class="stats-strip" aria-label="Live project statistics">
        <div>
            <span class="stat-kicker">REGISTERED CODERS</span>
            <strong><?= number_format($stats['users']) ?></strong>
        </div>
        <div>
            <span class="stat-kicker">PROBLEMS ONLINE</span>
            <strong><?= number_format($stats['problems']) ?></strong>
        </div>
        <div>
            <span class="stat-kicker">SUBMISSIONS TRACKED</span>
            <strong><?= number_format($stats['submissions']) ?></strong>
        </div>
        <div>
            <span class="stat-kicker">SYSTEM STATUS</span>
            <strong class="status-live"><i></i> LIVE</strong>
        </div>
    </section>

    <section class="feature-section" id="features">
        <div class="section-heading">
            <div>
                <span class="section-no">/ 01</span>
                <h2>BUILT TO<br><span>HIT HARDER.</span></h2>
            </div>
            <p>Not another passive dashboard. CodeForge turns your historical performance into competitive systems you can interact with.</p>
        </div>

        <div class="feature-grid">
            <a href="<?= $user ? 'code_dna.php' : 'login.php' ?>" class="feature-tile feature-red tilt-card">
                <span class="tile-index">01</span>
                <i class="bi bi-hexagon"></i>
                <h3>CODE DNA</h3>
                <p>Expose your strengths, weaknesses, speed, accuracy, consistency and topic mastery from real submission data.</p>
                <span class="tile-link">READ YOUR PROFILE <i class="bi bi-arrow-right"></i></span>
            </a>

            <a href="<?= $user ? 'ghost_race.php' : 'login.php' ?>" class="feature-tile feature-orange tilt-card">
                <span class="tile-index">02</span>
                <i class="bi bi-ghost"></i>
                <h3>GHOST RACE</h3>
                <p>Race against recorded historical solving sessions and beat the exact moment another coder reached AC.</p>
                <span class="tile-link">CHASE THE GHOST <i class="bi bi-arrow-right"></i></span>
            </a>

            <a href="<?= $user ? 'sql_battle.php' : 'login.php' ?>" class="feature-tile feature-cyan tilt-card">
                <span class="tile-index">03</span>
                <i class="bi bi-database-fill-gear"></i>
                <h3>SQL BATTLE</h3>
                <p>Fight deterministic SELECT-only database challenges scored by correctness, execution time and efficiency.</p>
                <span class="tile-link">ENTER THE ARENA <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    </section>

    <section class="arena-section" id="arena">
        <div class="terminal-shell tilt-card">
            <div class="terminal-top">
                <span><i></i><i></i><i></i></span>
                <b>codeforge://system/boot</b>
                <em>LIVE</em>
            </div>
            <div class="terminal-body">
                <div class="terminal-line"><span>01</span><code>$ initialize codeforge --mode=aggressive</code></div>
                <div class="terminal-line"><span>02</span><code class="terminal-output" data-terminal-line>loading performance intelligence...</code></div>
                <div class="terminal-line"><span>03</span><code class="terminal-output" data-terminal-line>mounting ghost-race timeline engine...</code></div>
                <div class="terminal-line"><span>04</span><code class="terminal-output" data-terminal-line>isolating SQL battle sandbox...</code></div>
                <div class="terminal-line active"><span>05</span><code><b>READY.</b> choose your next move_<i class="terminal-cursor"></i></code></div>
            </div>
        </div>

        <div class="arena-copy">
            <span class="section-no">/ 02</span>
            <h2>YOUR<br>COMMAND<br><span>CENTER.</span></h2>
            <p>One account. One performance history. Every system connected to the same competitive profile.</p>

            <?php if ($user): ?>
                <a class="text-action magnetic" href="dashboard.php">OPEN DASHBOARD <i class="bi bi-arrow-up-right"></i></a>
            <?php else: ?>
                <a class="text-action magnetic" href="register.php">JOIN CODEFORGE <i class="bi bi-arrow-up-right"></i></a>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <a class="landing-brand mini" href="index.php"><span class="brand-slash">&lt;/&gt;</span><span>CODE<span>FORGE</span></span></a>
    <p>Competitive programming intelligence laboratory.</p>
    <span>CODEFORGE // 2.0</span>
</footer>

<script src="assets/js/landing.js"></script>
</body>
</html>
