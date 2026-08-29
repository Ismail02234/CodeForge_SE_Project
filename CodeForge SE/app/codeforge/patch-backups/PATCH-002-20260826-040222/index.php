<?php
require_once __DIR__ . '/core/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$stats = [
    'users' => 0,
    'problems' => 0,
    'submissions' => 0,
    'contests' => 0,
];

try {
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['problems'] = (int) $pdo->query('SELECT COUNT(*) FROM problems')->fetchColumn();
    $stats['submissions'] = (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
    $stats['contests'] = (int) $pdo->query("SELECT COUNT(*) FROM contests WHERE status IN ('Active','Upcoming')")->fetchColumn();
} catch (Throwable $e) {
    // Landing page remains available even if statistics cannot be loaded.
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#050608">
    <title>CodeForge â€” Enter the Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="landing-page">
<canvas id="forgeCanvas" class="forge-canvas" aria-hidden="true"></canvas>
<div class="cursor-aura" id="cursorAura" aria-hidden="true"></div>
<div class="scanlines" aria-hidden="true"></div>

<header class="landing-nav">
    <a class="landing-brand" href="index.php" aria-label="CodeForge home">
        <span class="landing-brand-icon">CF</span>
        <span>CODE<span>FORGE</span></span>
    </a>
    <nav class="landing-links" aria-label="Landing navigation">
        <a href="#arsenal">Arsenal</a>
        <a href="#systems">Systems</a>
        <a href="#arena">Arena</a>
    </nav>
    <div class="landing-auth-actions">
        <a class="btn btn-ghost" href="login.php">Log In</a>
        <a class="btn btn-strike" href="register.php" data-magnetic>Create Account <i class="bi bi-arrow-up-right"></i></a>
    </div>
</header>

<main>
    <section class="landing-hero" id="arena">
        <div class="hero-noise" aria-hidden="true"></div>
        <div class="hero-copy">
            <div class="landing-kicker"><span></span> Competitive Intelligence Platform / v2.0</div>
            <h1>
                <span>CODE.</span>
                <span>CLASH.</span>
                <span class="impact-word">CONQUER.</span>
            </h1>
            <p class="landing-lead">A brutal training ground for programmers who want more than a solved counter. Read your Code DNA, hunt historical ghosts, and fight for query dominance.</p>
            <div class="landing-cta-row">
                <a class="btn btn-strike btn-lg" href="register.php" data-magnetic>ENTER CODEFORGE <i class="bi bi-chevron-double-right"></i></a>
                <a class="btn btn-ghost btn-lg" href="login.php">I ALREADY HAVE AN ACCOUNT</a>
            </div>
            <div class="hero-status-line">
                <span><i></i> SYSTEM ONLINE</span>
                <span><?= number_format($stats['users']) ?> CODERS</span>
                <span><?= number_format($stats['submissions']) ?> SUBMISSIONS</span>
            </div>
        </div>

        <div class="landing-visual" aria-hidden="true">
            <div class="landing-core" id="landingCore" data-tilt>
                <div class="core-ring ring-a"></div>
                <div class="core-ring ring-b"></div>
                <div class="core-ring ring-c"></div>
                <div class="core-cross cross-a"></div>
                <div class="core-cross cross-b"></div>
                <div class="core-center">
                    <span>&lt;/&gt;</span>
                    <strong>FORGE</strong>
                    <small>LIVE ENGINE</small>
                </div>
                <div class="core-node node-a">DNA</div>
                <div class="core-node node-b">SQL</div>
                <div class="core-node node-c">GHOST</div>
            </div>
            <div class="visual-caption">MOVE YOUR CURSOR // THE SYSTEM REACTS</div>
        </div>
    </section>

    <section class="landing-stats" aria-label="Live platform statistics">
        <article><strong><?= number_format($stats['problems']) ?></strong><span>PROBLEMS LOADED</span></article>
        <article><strong><?= number_format($stats['users']) ?></strong><span>REGISTERED CODERS</span></article>
        <article><strong><?= number_format($stats['submissions']) ?></strong><span>RECORDED ATTEMPTS</span></article>
        <article><strong><?= number_format($stats['contests']) ?></strong><span>ACTIVE / UPCOMING</span></article>
    </section>

    <section class="landing-section" id="arsenal">
        <div class="section-rail"><span>01</span><b>YOUR ARSENAL</b></div>
        <div class="landing-section-head">
            <div>
                <div class="landing-kicker"><span></span> Built for competitive pressure</div>
                <h2>THREE SYSTEMS.<br>ZERO COMFORT ZONE.</h2>
            </div>
            <p>Every flagship module is tied to real platform data. Train against weaknesses, replay pressure, and prove database skill under constraints.</p>
        </div>

        <div class="weapon-grid">
            <article class="weapon-card weapon-dna" data-tilt-card>
                <div class="weapon-index">01 //</div>
                <i class="bi bi-hexagon-half"></i>
                <h3>CODE DNA</h3>
                <p>Turn submissions into a measurable programming fingerprint across accuracy, speed, consistency, difficulty handling, and topic mastery.</p>
                <div class="weapon-tag">ANALYTICS ENGINE</div>
            </article>
            <article class="weapon-card weapon-ghost" data-tilt-card>
                <div class="weapon-index">02 //</div>
                <i class="bi bi-ghost"></i>
                <h3>GHOST RACE</h3>
                <p>Race the recorded timeline of another solver. Their failed attempts, timing and accepted finish become your moving target.</p>
                <div class="weapon-tag">HISTORICAL COMBAT</div>
            </article>
            <article class="weapon-card weapon-sql" data-tilt-card>
                <div class="weapon-index">03 //</div>
                <i class="bi bi-database-fill-gear"></i>
                <h3>SQL BATTLE</h3>
                <p>Write safe SELECT queries against an isolated arena dataset and compete on correctness, execution speed and efficiency.</p>
                <div class="weapon-tag">QUERY WARFARE</div>
            </article>
        </div>
    </section>

    <section class="landing-section systems-section" id="systems">
        <div class="section-rail"><span>02</span><b>COMBAT LOOP</b></div>
        <div class="combat-grid">
            <div class="combat-copy">
                <div class="landing-kicker"><span></span> One profile. Continuous pressure.</div>
                <h2>EVERY ATTEMPT<br>CHANGES THE READOUT.</h2>
                <p>CodeForge records meaningful performance events instead of treating practice as a static list. Your profile evolves as you submit, solve, race and battle.</p>
                <a class="text-strike" href="register.php">BUILD YOUR PROFILE <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="terminal-rig">
                <div class="terminal-top"><span></span><span></span><span></span><b>codeforge://telemetry</b></div>
                <pre><code><span class="term-muted">$</span> profile --scan current
<span class="term-red">[DNA]</span> accuracy........ <b>82%</b>
<span class="term-red">[DNA]</span> graph mastery... <b>91%</b>
<span class="term-orange">[GHOST]</span> target locked... <b>10:28</b>
<span class="term-cyan">[SQL]</span> arena status.... <b>READY</b>

<span class="term-muted">// no spectators. ship the query.</span><span class="terminal-caret">â–ˆ</span></code></pre>
            </div>
        </div>
    </section>

    <section class="landing-final-cta">
        <div>
            <div class="landing-kicker"><span></span> Ready when you are</div>
            <h2>STOP WATCHING.<br><em>ENTER THE FORGE.</em></h2>
        </div>
        <div class="final-actions">
            <a class="btn btn-strike btn-lg" href="register.php" data-magnetic>Create Account</a>
            <a class="btn btn-ghost btn-lg" href="login.php">Log In</a>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <a class="landing-brand" href="index.php"><span class="landing-brand-icon">CF</span><span>CODE<span>FORGE</span></span></a>
    <span>PLAIN PHP // PDO // MARIADB</span>
    <span>CODEFORGE 2.0</span>
</footer>

<script src="assets/js/landing.js"></script>
</body>
</html>