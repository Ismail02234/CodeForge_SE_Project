# PATCH-002-Dedicated-Landing-Auth.ps1
# CodeForge 2.0 - Dedicated public landing page + mouse-reactive animation + auth routing
# Run this file from D:\xampp\htdocs\codeforge

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

# Resolve project root.
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = $scriptDir
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    $root = (Get-Location).Path
}
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    throw "Could not find CodeForge project root. Put this patch inside D:\xampp\htdocs\codeforge and run it there."
}

Set-Location $root

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = Join-Path $root "patch-backups\PATCH-002-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null

function Backup-File($relative) {
    $src = Join-Path $root $relative
    if (Test-Path $src) {
        $dest = Join-Path $backup $relative
        $parent = Split-Path -Parent $dest
        New-Item -ItemType Directory -Force -Path $parent | Out-Null
        Copy-Item $src $dest -Force
    }
}

Write-Step "Creating backup"
@(
    "index.php",
    "dashboard.php",
    "login.php",
    "register.php",
    "logout.php",
    "core\auth.php",
    "includes\header.php",
    "assets\css\app.css",
    "assets\css\landing.css",
    "assets\css\aggressive.css",
    "assets\js\landing.js"
) | ForEach-Object { Backup-File $_ }

Write-Step "Preserving the current dashboard"

$indexPath = Join-Path $root "index.php"
$dashboardPath = Join-Path $root "dashboard.php"

# If the dashboard does not exist yet, preserve the old protected index.php as dashboard.php.
if (-not (Test-Path $dashboardPath)) {
    if (Test-Path $indexPath) {
        $indexContent = Get-Content $indexPath -Raw
        if ($indexContent -match "require_login" -or $indexContent -match "Command Center" -or $indexContent -match "Code DNA") {
            Copy-Item $indexPath $dashboardPath -Force
            Write-Host "Preserved old index.php as dashboard.php" -ForegroundColor Green
        }
    }
}

if (-not (Test-Path $dashboardPath)) {
    throw "dashboard.php could not be created. Restore from $backup and tell ChatGPT."
}

# Make sure dashboard remains protected.
$dashboardContent = Get-Content $dashboardPath -Raw
if ($dashboardContent -notmatch "require_login") {
    Write-Warning "dashboard.php does not appear to call require_login(). The patch will continue, but report this after testing."
}

Write-Step "Creating the dedicated public landing page"

$landingPhp = @'
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
'@

Set-Content -Path $indexPath -Value $landingPhp -Encoding UTF8

Write-Step "Creating the new Create Account page"

$registerPhp = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$error = null;
$universities = [];

try {
    $universities = $pdo->query('SELECT name FROM universities ORDER BY name')->fetchAll();
} catch (Throwable $e) {
    $universities = [];
}

if (request_method('POST')) {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $university = trim((string) ($_POST['university'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
        $error = 'Username must be 3–32 characters using letters, numbers or underscore.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);

        if ($stmt->fetch()) {
            $error = 'That username is already taken.';
        } else {
            if ($university !== '') {
                $checkUniversity = $pdo->prepare('SELECT name FROM universities WHERE name = :name LIMIT 1');
                $checkUniversity->execute(['name' => $university]);
                if (!$checkUniversity->fetch()) {
                    $university = '';
                }
            }

            $id = generate_id('u');
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();

                $insert = $pdo->prepare(
                    'INSERT INTO users (id, username, password, role, rating, university, `rank`)
                     VALUES (:id, :username, :password, \'user\', 1200, :university, \'Newbie\')'
                );
                $insert->execute([
                    'id' => $id,
                    'username' => $username,
                    'password' => $hash,
                    'university' => $university !== '' ? $university : null,
                ]);

                $log = $pdo->prepare(
                    "INSERT INTO activity_logs(user_id, action, details)
                     VALUES(:uid, 'auth.register', 'Created account')"
                );
                $log->execute(['uid' => $id]);

                $pdo->commit();

                login_user(['id' => $id]);
                flash('success', 'Welcome to CodeForge. Your account is ready.');
                redirect('dashboard.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Account creation failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Create account · CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/aggressive.css">
</head>
<body class="aggressive-auth-page">
<div class="auth-noise"></div>
<div class="auth-shell">
    <section class="auth-visual">
        <a class="auth-brand" href="index.php"><span>&lt;/&gt;</span> CODE<b>FORGE</b></a>
        <div class="auth-kicker"><i></i> NEW COMBATANT</div>
        <h1>ENTER<br>THE<br><span>FORGE.</span></h1>
        <p>Create your CodeForge identity. Every solve, failed attempt, race and SQL battle starts building your competitive profile.</p>
        <div class="auth-feature-list">
            <span><b>01</b> Code DNA performance intelligence</span>
            <span><b>02</b> Ghost Race historical competition</span>
            <span><b>03</b> SQL Battle sandbox</span>
        </div>
    </section>

    <section class="auth-panel">
        <a class="back-home" href="index.php"><i class="bi bi-arrow-left"></i> Back to landing</a>
        <div class="auth-panel-head">
            <span>CREATE ACCOUNT</span>
            <h2>Forge your identity.</h2>
            <p>Already registered? <a href="login.php">Log in here.</a></p>
        </div>

        <?php if ($error): ?>
            <div class="auth-error"><i class="bi bi-exclamation-triangle"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="aggressive-form">
            <?= csrf_field() ?>

            <label>
                <span>USERNAME</span>
                <input name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus autocomplete="username" placeholder="e.g. ShadowCoder">
            </label>

            <label>
                <span>UNIVERSITY <em>OPTIONAL</em></span>
                <select name="university">
                    <option value="">No university selected</option>
                    <?php foreach ($universities as $uni): ?>
                        <option value="<?= e($uni['name']) ?>" <?= (($_POST['university'] ?? '') === $uni['name']) ? 'selected' : '' ?>>
                            <?= e($uni['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="auth-two">
                <label>
                    <span>PASSWORD</span>
                    <input type="password" name="password" required autocomplete="new-password" placeholder="Minimum 8 characters">
                </label>
                <label>
                    <span>CONFIRM</span>
                    <input type="password" name="confirm_password" required autocomplete="new-password" placeholder="Repeat password">
                </label>
            </div>

            <button class="aggressive-submit" type="submit">
                CREATE ACCOUNT
                <i class="bi bi-arrow-up-right"></i>
            </button>
        </form>

        <div class="auth-security"><i class="bi bi-shield-check"></i> Passwords are stored using PHP password hashing.</div>
    </section>
</div>
</body>
</html>
'@

Set-Content -Path (Join-Path $root "register.php") -Value $registerPhp -Encoding UTF8

Write-Step "Replacing login routing and login UI"

$loginPhp = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$error = null;

if (request_method('POST')) {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string) $user['password'])) {
        login_user($user);

        try {
            $pdo->prepare(
                "INSERT INTO activity_logs(user_id,action,details)
                 VALUES(:uid,'auth.login','Signed in')"
            )->execute(['uid' => $user['id']]);
        } catch (Throwable $e) {
            // Logging must never block authentication.
        }

        redirect('dashboard.php');
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Log in · CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/aggressive.css">
</head>
<body class="aggressive-auth-page">
<div class="auth-noise"></div>
<div class="auth-shell">
    <section class="auth-visual">
        <a class="auth-brand" href="index.php"><span>&lt;/&gt;</span> CODE<b>FORGE</b></a>
        <div class="auth-kicker"><i></i> ACCESS TERMINAL</div>
        <h1>GET<br>BACK<br><span>IN.</span></h1>
        <p>Your command center is waiting. Continue your training history, DNA profile, races and arena battles.</p>
        <div class="auth-feature-list">
            <span><b>01</b> Resume your performance timeline</span>
            <span><b>02</b> Challenge historical ghosts</span>
            <span><b>03</b> Fight for SQL arena score</span>
        </div>
    </section>

    <section class="auth-panel">
        <a class="back-home" href="index.php"><i class="bi bi-arrow-left"></i> Back to landing</a>
        <div class="auth-panel-head">
            <span>LOG IN</span>
            <h2>Access CodeForge.</h2>
            <p>New here? <a href="register.php">Create an account.</a></p>
        </div>

        <?php if ($error): ?>
            <div class="auth-error"><i class="bi bi-exclamation-triangle"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="aggressive-form">
            <?= csrf_field() ?>

            <label>
                <span>USERNAME</span>
                <input name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="Your username">
            </label>

            <label>
                <span>PASSWORD</span>
                <input type="password" name="password" required autocomplete="current-password" placeholder="Your password">
            </label>

            <button class="aggressive-submit" type="submit">
                LOG IN
                <i class="bi bi-arrow-up-right"></i>
            </button>
        </form>

        <div class="demo-credentials">
            <span>DEMO ACCESS</span>
            <code>Ismail / 123456</code>
            <code>Admin / admin123</code>
        </div>
    </section>
</div>
</body>
</html>
'@

Set-Content -Path (Join-Path $root "login.php") -Value $loginPhp -Encoding UTF8

Write-Step "Fixing logout and protected-page routing"

$logoutPhp = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';
logout_user();
redirect('index.php');
'@
Set-Content -Path (Join-Path $root "logout.php") -Value $logoutPhp -Encoding UTF8

# Patch require_admin() so it returns to the dashboard, not public landing.
$authPath = Join-Path $root "core\auth.php"
if (Test-Path $authPath) {
    $authContent = Get-Content $authPath -Raw
    $authContent = $authContent.Replace("redirect('index.php');", "redirect('dashboard.php');")
    Set-Content $authPath $authContent -Encoding UTF8
}

# Patch the application header so Dashboard points to dashboard.php.
$headerPath = Join-Path $root "includes\header.php"
if (Test-Path $headerPath) {
    $headerContent = Get-Content $headerPath -Raw

    $headerContent = $headerContent.Replace('href="index.php"><span class="brand-mark"', 'href="dashboard.php"><span class="brand-mark"')
    $headerContent = $headerContent.Replace('$current===\'index.php\'', '$current===\'dashboard.php\'')
    $headerContent = $headerContent.Replace('href="index.php"><i class="bi bi-grid-1x2"></i>Dashboard', 'href="dashboard.php"><i class="bi bi-grid-1x2"></i>Dashboard')

    # Add aggressive.css after app.css if not already present.
    if ($headerContent -notmatch 'assets/css/aggressive\.css') {
        $headerContent = $headerContent.Replace(
            '<link rel="stylesheet" href="assets/css/app.css">',
            '<link rel="stylesheet" href="assets/css/app.css">' + [Environment]::NewLine + '    <link rel="stylesheet" href="assets/css/aggressive.css">'
        )
    }

    Set-Content $headerPath $headerContent -Encoding UTF8
}

Write-Step "Creating aggressive global visual layer"

$aggressiveCss = @'
:root{
    --forge-red:#ff2a1a;
    --forge-orange:#ff6a00;
    --forge-hot:#ff3b15;
    --forge-black:#050506;
    --forge-panel:#0a0a0d;
    --forge-line:rgba(255,255,255,.09);
}

/* Global app polish: this deliberately sits on top of app.css */
body:not(.forge-landing):not(.aggressive-auth-page){
    background:
        radial-gradient(circle at 82% 6%,rgba(255,42,26,.11),transparent 28%),
        radial-gradient(circle at 20% 85%,rgba(0,217,255,.05),transparent 27%),
        #05070a !important;
}

.sidebar{
    background:linear-gradient(180deg,#060608,#090a0d 60%,#060608)!important;
    border-right:1px solid rgba(255,255,255,.07)!important;
}
.sidebar:before{
    content:"";
    position:absolute;
    top:0;right:-1px;width:1px;height:22vh;
    background:linear-gradient(180deg,var(--forge-red),transparent);
    box-shadow:0 0 22px rgba(255,42,26,.75);
}
.brand-mark{
    border-radius:7px!important;
    background:linear-gradient(135deg,var(--forge-red),var(--forge-orange))!important;
    color:#fff!important;
    box-shadow:0 0 30px rgba(255,42,26,.28)!important;
    transform:skew(-5deg);
}
.sidebar-nav a{
    border-radius:7px!important;
}
.sidebar-nav a:hover{
    background:rgba(255,255,255,.045)!important;
}
.sidebar-nav a.active{
    color:#fff!important;
    border:1px solid rgba(255,70,35,.24)!important;
    background:linear-gradient(90deg,rgba(255,42,26,.14),rgba(255,106,0,.03))!important;
    box-shadow:inset 3px 0 0 var(--forge-red);
}
.sidebar-nav a.active i{
    color:#ff5a39!important;
}
.feature-link span{
    color:#ff6b45!important;
    background:rgba(255,42,26,.11)!important;
}
.topbar{
    background:rgba(5,6,8,.88)!important;
    border-bottom:1px solid rgba(255,255,255,.07)!important;
}
.global-search,
.form-control,.form-select,.code-editor,.input,.select,.textarea{
    background:#080a0e!important;
    border-color:rgba(255,255,255,.10)!important;
}
.global-search:focus-within,
.form-control:focus,.form-select:focus,.code-editor:focus,.input:focus,.select:focus,.textarea:focus{
    border-color:rgba(255,73,38,.65)!important;
    box-shadow:0 0 0 3px rgba(255,42,26,.09)!important;
}
.card,.hero{
    background:linear-gradient(180deg,#0c0e12,#080a0e)!important;
    border-color:rgba(255,255,255,.08)!important;
    border-radius:12px!important;
}
.hero:before{
    content:"";
    position:absolute;
    left:0;top:0;bottom:0;width:3px;
    background:linear-gradient(var(--forge-red),var(--forge-orange));
    box-shadow:0 0 18px rgba(255,42,26,.45);
}
.feature-card:before{
    background:linear-gradient(90deg,var(--forge-red),var(--forge-orange),transparent)!important;
}
.feature-icon{
    color:#ff5b38!important;
    background:rgba(255,42,26,.09)!important;
}
.eyebrow,.text-cyan{
    color:#ff6846!important;
}
.progress span{
    background:linear-gradient(90deg,var(--forge-red),var(--forge-orange))!important;
}
.btn{
    border-radius:7px!important;
}
.btn-primary{
    background:linear-gradient(135deg,var(--forge-red),var(--forge-orange))!important;
    color:#fff!important;
    box-shadow:0 10px 30px rgba(255,42,26,.12);
}
.btn-primary:hover{
    box-shadow:0 14px 34px rgba(255,42,26,.24)!important;
}
.data-table tbody tr:hover,.table tbody tr:hover{
    background:rgba(255,42,26,.035)!important;
}

/* Aggressive login + registration */
.aggressive-auth-page{
    margin:0;
    min-height:100vh;
    background:
        radial-gradient(circle at 15% 18%,rgba(255,42,26,.13),transparent 23%),
        radial-gradient(circle at 83% 77%,rgba(255,106,0,.07),transparent 22%),
        #050506;
    color:#f5f5f5;
    font-family:Inter,system-ui,sans-serif;
    overflow-x:hidden;
}
.auth-noise{
    position:fixed;inset:0;pointer-events:none;opacity:.15;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.16'/%3E%3C/svg%3E");
    mix-blend-mode:soft-light;
}
.auth-shell{
    width:min(1180px,calc(100% - 36px));
    min-height:min(760px,calc(100vh - 56px));
    margin:28px auto;
    display:grid;
    grid-template-columns:1.05fr .95fr;
    border:1px solid rgba(255,255,255,.09);
    background:#08090c;
    box-shadow:0 40px 120px rgba(0,0,0,.55);
    position:relative;
    z-index:1;
}
.auth-visual{
    padding:48px clamp(34px,5vw,70px);
    min-height:700px;
    display:flex;
    flex-direction:column;
    position:relative;
    overflow:hidden;
    background:
        linear-gradient(135deg,rgba(255,42,26,.08),transparent 38%),
        repeating-linear-gradient(90deg,transparent 0 79px,rgba(255,255,255,.025) 80px),
        repeating-linear-gradient(0deg,transparent 0 79px,rgba(255,255,255,.025) 80px),
        #070709;
}
.auth-visual:after{
    content:"";
    width:340px;height:340px;border:1px solid rgba(255,68,32,.26);
    position:absolute;right:-120px;bottom:-100px;
    transform:rotate(45deg);
    box-shadow:0 0 80px rgba(255,42,26,.10), inset 0 0 80px rgba(255,42,26,.05);
}
.auth-brand{
    display:flex;align-items:center;gap:10px;
    font-weight:900;letter-spacing:-.035em;font-size:18px;
    color:#fff;text-decoration:none;
}
.auth-brand>span{
    padding:8px 9px;background:linear-gradient(135deg,var(--forge-red),var(--forge-orange));
    font-family:JetBrains Mono,monospace;
}
.auth-brand b{color:#ff5435}
.auth-kicker{
    margin-top:auto;
    display:flex;align-items:center;gap:9px;
    color:#ff6a48;font:700 10px/1 JetBrains Mono,monospace;
    letter-spacing:.15em;
}
.auth-kicker i{width:7px;height:7px;background:#ff3a22;box-shadow:0 0 16px #ff3a22}
.auth-visual h1{
    margin:24px 0 16px;
    font-size:clamp(58px,7vw,90px);
    line-height:.82;
    letter-spacing:-.075em;
    font-weight:900;
}
.auth-visual h1 span{
    color:#ff4228;
    text-shadow:0 0 35px rgba(255,42,26,.18);
}
.auth-visual p{
    max-width:500px;color:#8d8f97;line-height:1.75;font-size:13px;
}
.auth-feature-list{
    display:grid;gap:9px;margin-top:30px;
}
.auth-feature-list span{
    font:600 10px/1.5 JetBrains Mono,monospace;
    color:#a6a8af;
}
.auth-feature-list b{color:#ff4b2e;margin-right:10px}
.auth-panel{
    padding:48px clamp(30px,5vw,64px);
    display:flex;
    flex-direction:column;
    justify-content:center;
    background:linear-gradient(180deg,#0c0d11,#08090c);
}
.back-home{
    position:absolute;top:22px;
    color:#7e8088;font-size:11px;text-decoration:none;
}
.back-home:hover{color:#fff}
.auth-panel-head>span{
    color:#ff5838;font:700 10px/1 JetBrains Mono,monospace;letter-spacing:.16em;
}
.auth-panel-head h2{
    margin:10px 0 7px;font-size:34px;letter-spacing:-.045em;
}
.auth-panel-head p{
    margin:0 0 28px;color:#74767d;font-size:12px;
}
.auth-panel-head a{color:#ff6242}
.aggressive-form{
    display:grid;gap:16px;
}
.aggressive-form label{
    display:grid;gap:8px;
}
.aggressive-form label>span{
    font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.14em;color:#94969e;
}
.aggressive-form label>span em{
    color:#4d4f56;font-style:normal;margin-left:6px;
}
.aggressive-form input,.aggressive-form select{
    width:100%;box-sizing:border-box;
    padding:14px 15px;
    color:#fff;background:#07080b;
    border:1px solid #23252c;outline:0;
    border-radius:0;
}
.aggressive-form input:focus,.aggressive-form select:focus{
    border-color:#ff4a2d;
    box-shadow:0 0 0 3px rgba(255,42,26,.08);
}
.aggressive-form select option{background:#090a0d}
.auth-two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.aggressive-submit{
    margin-top:8px;min-height:52px;border:0;
    display:flex;align-items:center;justify-content:space-between;
    padding:0 18px;
    background:linear-gradient(100deg,#ff2617,#ff7400);
    color:#fff;font-weight:900;letter-spacing:.025em;
    cursor:pointer;
    transition:.2s ease;
}
.aggressive-submit:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 45px rgba(255,42,26,.24);
}
.auth-error{
    display:flex;gap:9px;align-items:center;
    padding:12px 13px;margin-bottom:18px;
    background:rgba(255,42,26,.08);border:1px solid rgba(255,58,31,.25);
    color:#ff8b77;font-size:12px;
}
.demo-credentials{
    margin-top:18px;padding:12px 0 0;border-top:1px solid #1b1d22;
    display:flex;flex-wrap:wrap;gap:8px;align-items:center;
}
.demo-credentials>span{
    width:100%;font:700 9px/1 JetBrains Mono,monospace;color:#555860;letter-spacing:.12em;
}
.demo-credentials code,.auth-security{
    color:#777b83;font:600 10px/1.5 JetBrains Mono,monospace;
}
.auth-security{margin-top:18px}
.auth-security i{color:#ff5f40;margin-right:5px}

@media(max-width:900px){
    .auth-shell{grid-template-columns:1fr}
    .auth-visual{display:none}
    .auth-panel{min-height:calc(100vh - 56px)}
    .back-home{position:static;margin-bottom:40px}
}
@media(max-width:560px){
    .auth-shell{width:100%;margin:0;min-height:100vh;border:0}
    .auth-panel{padding:28px 20px}
    .auth-two{grid-template-columns:1fr}
}
'@

Set-Content -Path (Join-Path $root "assets\css\aggressive.css") -Value $aggressiveCss -Encoding UTF8

Write-Step "Creating landing page styles"

$landingCss = @'
:root{
    --red:#ff2c18;
    --red2:#ff4b22;
    --orange:#ff7900;
    --cyan:#29e7ff;
    --ink:#050506;
    --panel:#0a0a0c;
    --line:rgba(255,255,255,.09);
    --text:#f6f6f4;
    --muted:#85868c;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    margin:0;
    background:#050506;
    color:var(--text);
    font-family:Inter,system-ui,sans-serif;
    overflow-x:hidden;
}
a{color:inherit;text-decoration:none}
button,input{font:inherit}
::selection{background:var(--red);color:#fff}

#forgeCanvas{
    position:fixed;inset:0;width:100%;height:100%;
    z-index:0;pointer-events:none;opacity:.78;
}
.cursor-glow{
    position:fixed;
    width:420px;height:420px;border-radius:50%;
    transform:translate(-50%,-50%);
    background:radial-gradient(circle,rgba(255,47,24,.105),rgba(255,47,24,.025) 36%,transparent 70%);
    pointer-events:none;z-index:1;
    mix-blend-mode:screen;
}
.noise{
    position:fixed;inset:0;z-index:2;pointer-events:none;opacity:.16;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.92' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.18'/%3E%3C/svg%3E");
    mix-blend-mode:soft-light;
}
main,.landing-nav,.landing-footer{position:relative;z-index:3}

.landing-nav{
    width:min(1480px,calc(100% - 52px));
    height:86px;margin:0 auto;
    display:flex;align-items:center;gap:32px;
    border-bottom:1px solid rgba(255,255,255,.065);
}
.landing-brand{
    display:flex;align-items:center;gap:10px;
    font-weight:900;font-size:18px;letter-spacing:-.045em;
}
.landing-brand>span:last-child>span{color:#ff4a2c}
.brand-slash{
    width:38px;height:38px;display:grid;place-items:center;
    background:linear-gradient(135deg,var(--red),var(--orange));
    font:800 13px/1 JetBrains Mono,monospace;
    transform:skew(-5deg);
    box-shadow:0 0 35px rgba(255,44,24,.19);
}
.landing-links{
    margin-left:34px;display:flex;gap:28px;
}
.landing-links a{
    color:#76777d;font-size:10px;font-weight:800;
    text-transform:uppercase;letter-spacing:.13em;
    transition:.18s ease;
}
.landing-links a:hover{color:#fff}
.landing-actions{
    margin-left:auto;display:flex;align-items:center;gap:9px;
}
.nav-btn{
    min-height:38px;display:inline-flex;align-items:center;justify-content:center;
    padding:0 15px;font-size:10px;font-weight:900;letter-spacing:.07em;
    text-transform:uppercase;border:1px solid #292a2f;
}
.nav-btn-ghost{background:rgba(255,255,255,.02)}
.nav-btn-hot{
    background:linear-gradient(110deg,var(--red),var(--orange));
    border-color:transparent;
}
.signed-chip{
    display:inline-flex;align-items:center;gap:7px;
    color:#8f9298;font:700 10px/1 JetBrains Mono,monospace;
    margin-right:6px;
}
.signed-chip i{font-size:7px;color:#50ef9c;text-shadow:0 0 10px #50ef9c}

.landing-hero{
    width:min(1480px,calc(100% - 52px));
    min-height:calc(100vh - 86px);
    margin:0 auto;
    display:grid;grid-template-columns:minmax(0,1.03fr) minmax(440px,.97fr);
    align-items:center;gap:5vw;
    position:relative;
    padding:70px 0 92px;
}
.hero-copy{position:relative;z-index:2}
.system-tag{
    display:inline-flex;align-items:center;gap:9px;
    color:#a1a2a8;font:700 9px/1.2 JetBrains Mono,monospace;
    letter-spacing:.13em;
    margin-bottom:28px;
}
.pulse-dot{
    width:7px;height:7px;background:var(--red);display:block;
    box-shadow:0 0 0 0 rgba(255,44,24,.55);
    animation:pulse 1.8s infinite;
}
@keyframes pulse{
    0%{box-shadow:0 0 0 0 rgba(255,44,24,.52)}
    70%{box-shadow:0 0 0 10px rgba(255,44,24,0)}
    100%{box-shadow:0 0 0 0 rgba(255,44,24,0)}
}
.landing-hero h1{
    margin:0;
    font-size:clamp(78px,10vw,164px);
    font-weight:900;
    line-height:.75;
    letter-spacing:-.085em;
    max-width:950px;
}
.cut-text{
    display:inline-block;
    color:#ff321d;
    position:relative;
    text-shadow:0 0 52px rgba(255,44,24,.13);
}
.cut-text:after{
    content:"";
    position:absolute;left:1%;right:-2%;top:54%;height:4px;
    background:#050506;
    box-shadow:0 1px 0 rgba(255,255,255,.09);
    transform:rotate(-1deg);
}
.hero-lead{
    max-width:690px;
    margin:34px 0 0;
    color:#898b91;
    font-size:clamp(13px,1.1vw,16px);
    line-height:1.75;
}
.hero-cta{
    display:flex;flex-wrap:wrap;gap:10px;margin-top:34px;
}
.cta-primary,.cta-secondary{
    min-height:54px;display:inline-flex;align-items:center;justify-content:space-between;
    gap:24px;padding:0 20px;
    font-size:10px;font-weight:900;letter-spacing:.07em;
    border:1px solid;
    transition:.2s ease;
}
.cta-primary{
    min-width:250px;background:linear-gradient(100deg,var(--red),var(--orange));
    border-color:transparent;
    box-shadow:0 15px 50px rgba(255,44,24,.12);
}
.cta-secondary{border-color:#2a2b30;background:#08080a;color:#b1b2b6}
.cta-primary:hover,.cta-secondary:hover{transform:translateY(-2px)}
.hero-meta{
    display:flex;gap:25px;margin-top:43px;
    color:#5d5f66;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.12em;
}
.hero-meta b{color:#ff4a2d;margin-right:5px}
.hero-index{
    position:absolute;left:-1px;bottom:24px;
    color:#33353b;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.16em;
}

.forge-visual{
    height:min(650px,70vh);
    min-height:520px;
    position:relative;
    display:grid;place-items:center;
    perspective:1100px;
}
.forge-visual:before{
    content:"";
    position:absolute;inset:6% 3%;
    background:
        linear-gradient(rgba(255,255,255,.028) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.028) 1px,transparent 1px);
    background-size:48px 48px;
    mask-image:radial-gradient(circle,black 28%,transparent 73%);
}
.forge-halo{
    position:absolute;border-radius:50%;
    border:1px solid rgba(255,255,255,.075);
    animation:spin 18s linear infinite;
}
.halo-a{width:530px;height:530px;border-top-color:rgba(255,55,31,.6)}
.halo-b{width:420px;height:420px;animation-direction:reverse;animation-duration:13s;border-right-color:rgba(255,116,0,.48)}
.halo-c{width:620px;height:620px;animation-duration:24s;border-bottom-color:rgba(41,231,255,.16)}
@keyframes spin{to{transform:rotate(360deg)}}

.forge-core{
    width:310px;height:310px;
    position:relative;
    display:grid;place-items:center;
    transform-style:preserve-3d;
    transition:transform .12s ease-out;
}
.forge-core:before,.forge-core:after{
    content:"";position:absolute;inset:24px;
    border:1px solid rgba(255,71,39,.24);
    transform:rotate(45deg);
    box-shadow:0 0 70px rgba(255,44,24,.10),inset 0 0 80px rgba(255,44,24,.04);
}
.forge-core:after{inset:58px;transform:rotate(-16deg);border-color:rgba(255,255,255,.09)}
.core-grid{
    position:absolute;inset:48px;
    background:
        linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
    background-size:22px 22px;
    transform:rotate(45deg);
}
.core-ring{
    position:absolute;border-radius:50%;border:1px dashed rgba(255,255,255,.12);
}
.core-ring-a{inset:0;animation:spin 15s linear infinite}
.core-ring-b{inset:31px;border-style:solid;border-color:rgba(255,69,36,.25);animation:spin 8s linear infinite reverse}
.core-center{
    width:140px;height:140px;
    border-radius:50%;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    position:relative;z-index:3;
    background:radial-gradient(circle at 42% 34%,#30100b,#0b090a 62%);
    border:1px solid rgba(255,71,36,.42);
    box-shadow:0 0 90px rgba(255,44,24,.22),inset 0 0 40px rgba(255,44,24,.08);
}
.core-code{font:800 32px/1 JetBrains Mono,monospace;color:#ff5335}
.core-center small{
    margin-top:10px;color:#6c6e74;font:700 7px/1 JetBrains Mono,monospace;letter-spacing:.14em;
}
.core-center strong{
    margin-top:5px;color:#62efa6;font:800 8px/1 JetBrains Mono,monospace;letter-spacing:.17em;
}

.float-card{
    position:absolute;
    min-width:150px;padding:13px 14px;
    background:rgba(8,8,10,.86);
    border:1px solid rgba(255,255,255,.10);
    backdrop-filter:blur(12px);
    box-shadow:0 20px 65px rgba(0,0,0,.35);
}
.float-card small{
    display:block;color:#5b5d63;font:700 7px/1 JetBrains Mono,monospace;letter-spacing:.13em;
}
.float-card strong{
    display:block;margin-top:8px;font:800 27px/1 JetBrains Mono,monospace;
}
.float-card strong span{font-size:11px;color:#74767d}
.float-card>span{font:700 7px/1.5 JetBrains Mono,monospace;color:#777980}
.float-card .danger{color:#ff5032}
.float-card-a{left:2%;top:20%}
.float-card-b{right:0;top:34%}
.float-card-c{left:12%;bottom:9%}
.micro-bars{height:23px;display:flex;align-items:flex-end;gap:3px;margin-top:8px}
.micro-bars i{width:7px;height:var(--h);background:linear-gradient(#ff321d,#ff7900)}

.stats-strip{
    width:min(1480px,calc(100% - 52px));
    margin:0 auto;
    display:grid;grid-template-columns:repeat(4,1fr);
    border-top:1px solid var(--line);border-bottom:1px solid var(--line);
}
.stats-strip>div{
    min-height:120px;padding:24px 28px;
    display:flex;flex-direction:column;justify-content:center;
    border-right:1px solid var(--line);
}
.stats-strip>div:last-child{border-right:0}
.stat-kicker{
    color:#595b61;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.14em;
}
.stats-strip strong{
    margin-top:10px;font-size:30px;letter-spacing:-.045em;
}
.status-live{color:#55e99e!important;font-size:19px!important}
.status-live i{
    width:7px;height:7px;background:#55e99e;display:inline-block;margin-right:7px;
    box-shadow:0 0 12px #55e99e;
}

.feature-section{
    width:min(1480px,calc(100% - 52px));margin:0 auto;
    padding:140px 0 120px;
}
.section-heading{
    display:grid;grid-template-columns:1fr .7fr;
    align-items:end;gap:10vw;margin-bottom:60px;
}
.section-no{
    color:#ff4d2e;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.15em;
}
.section-heading h2,.arena-copy h2{
    margin:15px 0 0;
    font-size:clamp(58px,7vw,110px);
    line-height:.82;letter-spacing:-.075em;font-weight:900;
}
.section-heading h2 span,.arena-copy h2 span{color:#ff3e24}
.section-heading p{
    color:#7b7d83;line-height:1.75;font-size:13px;max-width:470px;margin:0 0 7px;
}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.feature-tile{
    min-height:420px;padding:28px;
    display:flex;flex-direction:column;
    position:relative;overflow:hidden;
    background:linear-gradient(180deg,#0b0b0e,#070709);
    border:1px solid rgba(255,255,255,.085);
    transition:transform .16s ease-out,border-color .2s ease;
    transform-style:preserve-3d;
}
.feature-tile:before{
    content:"";position:absolute;left:0;top:0;right:0;height:3px;
    background:var(--accent);
}
.feature-tile:after{
    content:"";position:absolute;width:250px;height:250px;border-radius:50%;
    right:-130px;top:-120px;background:radial-gradient(circle,var(--glow),transparent 68%);
}
.feature-red{--accent:#ff311c;--glow:rgba(255,49,28,.13)}
.feature-orange{--accent:#ff7900;--glow:rgba(255,121,0,.12)}
.feature-cyan{--accent:#28def4;--glow:rgba(40,222,244,.10)}
.tile-index{
    color:#4d4f55;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.15em;
}
.feature-tile>i{
    margin-top:72px;font-size:34px;color:var(--accent);
}
.feature-tile h3{
    margin:22px 0 12px;font-size:29px;letter-spacing:-.045em;
}
.feature-tile p{
    margin:0;color:#7d7f86;font-size:12px;line-height:1.75;max-width:360px;
}
.tile-link{
    margin-top:auto;color:#a7a8ad;font:800 8px/1 JetBrains Mono,monospace;letter-spacing:.12em;
}
.tile-link i{margin-left:6px}
.feature-tile:hover{border-color:color-mix(in srgb,var(--accent) 40%,transparent)}

.arena-section{
    width:min(1480px,calc(100% - 52px));margin:0 auto;
    padding:20px 0 150px;
    display:grid;grid-template-columns:1.25fr .75fr;gap:7vw;align-items:center;
}
.terminal-shell{
    background:#07080a;border:1px solid rgba(255,255,255,.09);
    box-shadow:0 35px 100px rgba(0,0,0,.38);
}
.terminal-top{
    min-height:48px;padding:0 16px;display:flex;align-items:center;
    border-bottom:1px solid rgba(255,255,255,.075);
    color:#5f6168;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.10em;
}
.terminal-top>span{display:flex;gap:5px;margin-right:17px}
.terminal-top>span i{width:7px;height:7px;background:#27292e}
.terminal-top>span i:first-child{background:#ff492e}
.terminal-top b{font-weight:600}
.terminal-top em{margin-left:auto;color:#59e8a2;font-style:normal}
.terminal-body{padding:28px 24px 30px;display:grid;gap:17px}
.terminal-line{
    display:grid;grid-template-columns:30px 1fr;gap:12px;
    color:#85878f;font:600 11px/1.7 JetBrains Mono,monospace;
}
.terminal-line>span{color:#32343a}
.terminal-line code{white-space:normal}
.terminal-line.active code{color:#c8c9cc}
.terminal-line.active b{color:#ff5335}
.terminal-cursor{
    display:inline-block;width:7px;height:13px;background:#ff4d2e;margin-left:3px;vertical-align:-2px;
    animation:blink .8s steps(1) infinite;
}
@keyframes blink{50%{opacity:0}}
.arena-copy p{color:#7d7f85;line-height:1.75;font-size:13px;max-width:430px}
.text-action{
    margin-top:20px;display:inline-flex;gap:28px;align-items:center;
    color:#ff5436;font:900 9px/1 JetBrains Mono,monospace;letter-spacing:.14em;
}

.landing-footer{
    width:min(1480px,calc(100% - 52px));min-height:105px;margin:0 auto;
    border-top:1px solid var(--line);
    display:flex;align-items:center;gap:30px;
    color:#4c4e54;font:600 9px/1.5 JetBrains Mono,monospace;
}
.landing-footer p{margin-left:auto}
.landing-brand.mini{font-size:14px}
.landing-brand.mini .brand-slash{width:30px;height:30px;font-size:10px}

@media(max-width:1100px){
    .landing-hero{grid-template-columns:1fr;min-height:auto;padding-top:90px}
    .forge-visual{height:620px}
    .section-heading{grid-template-columns:1fr}
    .section-heading p{margin-top:28px}
    .feature-grid{grid-template-columns:1fr}
    .feature-tile{min-height:300px}
    .feature-tile>i{margin-top:35px}
    .arena-section{grid-template-columns:1fr}
}
@media(max-width:760px){
    .landing-nav,.landing-hero,.stats-strip,.feature-section,.arena-section,.landing-footer{
        width:min(100% - 28px,1480px)
    }
    .landing-nav{height:72px}
    .landing-links{display:none}
    .landing-actions .signed-chip{display:none}
    .nav-btn{padding:0 11px}
    .nav-btn-ghost{display:none}
    .landing-hero{padding:70px 0 65px}
    .landing-hero h1{font-size:clamp(68px,20vw,118px)}
    .hero-meta{gap:12px;flex-wrap:wrap}
    .forge-visual{height:470px;min-height:470px;transform:scale(.83);margin:-30px -7vw}
    .float-card-a{left:4%}.float-card-b{right:3%}
    .stats-strip{grid-template-columns:1fr 1fr}
    .stats-strip>div{border-bottom:1px solid var(--line)}
    .feature-section{padding:95px 0}
    .section-heading h2,.arena-copy h2{font-size:62px}
    .arena-section{padding-bottom:100px}
    .landing-footer{flex-wrap:wrap;padding:30px 0}
    .landing-footer p{margin-left:0;width:100%}
}
@media(max-width:500px){
    .landing-brand{font-size:15px}
    .brand-slash{width:33px;height:33px}
    .nav-btn-hot{font-size:8px}
    .landing-hero h1{font-size:65px}
    .hero-lead{font-size:12px}
    .hero-cta{display:grid}
    .cta-primary,.cta-secondary{width:100%}
    .hero-meta{font-size:7px}
    .forge-visual{height:390px;min-height:390px;transform:scale(.69);margin:-55px -24vw}
    .stats-strip{grid-template-columns:1fr}
    .stats-strip>div{border-right:0}
    .feature-tile{padding:22px}
    .section-heading h2,.arena-copy h2{font-size:52px}
    .terminal-body{padding:22px 14px}
    .terminal-line{font-size:9px}
}
@media(pointer:coarse){
    .cursor-glow{display:none}
}
'@

Set-Content -Path (Join-Path $root "assets\css\landing.css") -Value $landingCss -Encoding UTF8

Write-Step "Creating mouse-reactive landing animation"

$landingJs = @'
(() => {
    const canvas = document.getElementById('forgeCanvas');
    const ctx = canvas?.getContext('2d');
    const glow = document.getElementById('cursorGlow');
    const core = document.getElementById('forgeCore');
    const parallaxEls = [...document.querySelectorAll('[data-parallax]')];
    const magnetic = [...document.querySelectorAll('.magnetic')];
    const tiltCards = [...document.querySelectorAll('.tilt-card')];

    let width = innerWidth;
    let height = innerHeight;
    let dpr = Math.min(devicePixelRatio || 1, 2);
    let mouse = { x: width * 0.5, y: height * 0.5, tx: width * 0.5, ty: height * 0.5, active: false };
    let particles = [];

    function resize() {
        width = innerWidth;
        height = innerHeight;
        dpr = Math.min(devicePixelRatio || 1, 2);

        if (canvas && ctx) {
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        const amount = Math.max(34, Math.min(92, Math.floor((width * height) / 18500)));
        particles = Array.from({ length: amount }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.16,
            vy: (Math.random() - 0.5) * 0.16,
            size: Math.random() * 1.25 + 0.35,
            hot: Math.random() > 0.82
        }));
    }

    function draw() {
        if (!ctx) return;

        ctx.clearRect(0, 0, width, height);

        mouse.x += (mouse.tx - mouse.x) * 0.09;
        mouse.y += (mouse.ty - mouse.y) * 0.09;

        for (const p of particles) {
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < -20) p.x = width + 20;
            if (p.x > width + 20) p.x = -20;
            if (p.y < -20) p.y = height + 20;
            if (p.y > height + 20) p.y = -20;

            if (mouse.active) {
                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const distSq = dx * dx + dy * dy;
                if (distSq < 190 * 190 && distSq > 10) {
                    const dist = Math.sqrt(distSq);
                    const pull = (1 - dist / 190) * 0.022;
                    p.x += dx * pull;
                    p.y += dy * pull;
                }
            }

            ctx.beginPath();
            ctx.fillStyle = p.hot ? 'rgba(255,69,35,.72)' : 'rgba(255,255,255,.22)';
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fill();

            if (mouse.active) {
                const dx = mouse.x - p.x;
                const dy = mouse.y - p.y;
                const dist = Math.hypot(dx, dy);

                if (dist < 155) {
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(255,67,34,${(1 - dist / 155) * 0.22})`;
                    ctx.lineWidth = 0.6;
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(draw);
    }

    function pointerMove(e) {
        mouse.tx = e.clientX;
        mouse.ty = e.clientY;
        mouse.active = true;

        if (glow) {
            glow.style.left = `${e.clientX}px`;
            glow.style.top = `${e.clientY}px`;
        }

        const nx = (e.clientX / width - 0.5) * 2;
        const ny = (e.clientY / height - 0.5) * 2;

        for (const el of parallaxEls) {
            const amount = parseFloat(el.dataset.parallax || '0');
            el.style.transform = `translate3d(${nx * width * amount}px, ${ny * height * amount}px, 0)`;
        }

        if (core) {
            core.style.transform = `rotateX(${-ny * 7}deg) rotateY(${nx * 9}deg) translate3d(${nx * 7}px,${ny * 7}px,0)`;
        }
    }

    addEventListener('pointermove', pointerMove, { passive: true });
    addEventListener('pointerleave', () => { mouse.active = false; });
    addEventListener('resize', resize);

    magnetic.forEach(el => {
        el.addEventListener('pointermove', e => {
            const r = el.getBoundingClientRect();
            const x = e.clientX - r.left - r.width / 2;
            const y = e.clientY - r.top - r.height / 2;
            el.style.transform = `translate(${x * .09}px, ${y * .09}px)`;
        });
        el.addEventListener('pointerleave', () => {
            el.style.transform = '';
        });
    });

    tiltCards.forEach(card => {
        card.addEventListener('pointermove', e => {
            if (innerWidth < 900) return;
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - .5;
            const y = (e.clientY - r.top) / r.height - .5;
            card.style.transform = `perspective(900px) rotateX(${-y * 3.7}deg) rotateY(${x * 4.2}deg) translateY(-2px)`;
        });
        card.addEventListener('pointerleave', () => {
            card.style.transform = '';
        });
    });

    const terminalLines = [...document.querySelectorAll('[data-terminal-line]')];
    terminalLines.forEach((line, index) => {
        const original = line.textContent;
        line.textContent = '';
        setTimeout(() => {
            let i = 0;
            const timer = setInterval(() => {
                line.textContent = original.slice(0, ++i);
                if (i >= original.length) clearInterval(timer);
            }, 18);
        }, 500 + index * 620);
    });

    resize();
    draw();
})();
'@

Set-Content -Path (Join-Path $root "assets\js\landing.js") -Value $landingJs -Encoding UTF8

Write-Step "Checking for remaining dashboard links pointing to index.php"

# Replace only common protected-page redirects/links in PHP files, excluding the public landing/auth pages.
Get-ChildItem -Path $root -Filter "*.php" -File -Recurse | Where-Object {
    $_.FullName -notlike "*\patch-backups\*" -and
    $_.Name -notin @("index.php","login.php","register.php","logout.php")
} | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $original = $content

    # Only replace obvious dashboard redirects; do not rewrite arbitrary public-home links.
    $content = $content.Replace("redirect('index.php');", "redirect('dashboard.php');")
    $content = $content.Replace('href="index.php"><i class="bi bi-grid-1x2"></i>Dashboard', 'href="dashboard.php"><i class="bi bi-grid-1x2"></i>Dashboard')

    if ($content -ne $original) {
        Set-Content $_.FullName $content -Encoding UTF8
    }
}

Write-Step "Running PHP syntax checks"

$phpExe = "php"
$xamppPhp = "D:\xampp\php\php.exe"
if (Test-Path $xamppPhp) {
    $phpExe = $xamppPhp
}

$filesToLint = @(
    "index.php",
    "dashboard.php",
    "login.php",
    "register.php",
    "logout.php",
    "core\auth.php",
    "includes\header.php"
)

$lintFailed = $false
foreach ($file in $filesToLint) {
    $full = Join-Path $root $file
    if (Test-Path $full) {
        $output = & $phpExe -l $full 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[FAIL] $file" -ForegroundColor Red
            Write-Host $output
            $lintFailed = $true
        } else {
            Write-Host "[OK]   $file" -ForegroundColor Green
        }
    }
}

if ($lintFailed) {
    Write-Host ""
    Write-Host "PATCH COMPLETED, BUT PHP LINT FOUND AN ERROR." -ForegroundColor Yellow
    Write-Host "Backup is at: $backup"
    exit 1
}

Write-Step "Patch complete"
Write-Host "PATCH-002 APPLIED SUCCESSFULLY" -ForegroundColor Green
Write-Host ""
Write-Host "Landing:        http://localhost/codeforge/" -ForegroundColor White
Write-Host "Create account: http://localhost/codeforge/register.php" -ForegroundColor White
Write-Host "Log in:         http://localhost/codeforge/login.php" -ForegroundColor White
Write-Host "Dashboard:      http://localhost/codeforge/dashboard.php" -ForegroundColor White
Write-Host ""
Write-Host "IMPORTANT: The landing page NO LONGER redirects logged-in users." -ForegroundColor Yellow
Write-Host "Even when you are logged in, /codeforge/ remains the dedicated landing page." -ForegroundColor Yellow
Write-Host ""
Write-Host "Backup: $backup" -ForegroundColor DarkGray
