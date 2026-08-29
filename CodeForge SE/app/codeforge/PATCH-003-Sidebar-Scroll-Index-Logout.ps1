# PATCH-003-Sidebar-Scroll-Index-Logout.ps1
# Fixes:
# 1) sidebar scrolls independently instead of scrolling the main page
# 2) index.php is forced to be the dedicated public landing page
# 3) logout button is visible when logged in (landing + app topbar)
# Run from D:\xampp\htdocs\codeforge

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = $scriptDir
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    $root = (Get-Location).Path
}
if (-not (Test-Path (Join-Path $root "core\bootstrap.php"))) {
    throw "Could not find the CodeForge project root. Put this patch in D:\xampp\htdocs\codeforge and run it there."
}

Set-Location $root

$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backup = Join-Path $root "patch-backups\PATCH-003-$stamp"
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

Write-Step "Backing up affected files"
@(
    "index.php",
    "dashboard.php",
    "includes\header.php",
    "assets\css\aggressive.css",
    "assets\css\landing.css"
) | ForEach-Object { Backup-File $_ }

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

Write-Step "Forcing index.php to be the dedicated public landing page"

# This is intentionally a real public index.php. It does NOT call require_login()
# and it does NOT redirect authenticated users to dashboard.php.
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
            <a class="nav-btn nav-btn-danger magnetic" href="logout.php"><i class="bi bi-box-arrow-right"></i> Log out</a>
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

[System.IO.File]::WriteAllText((Join-Path $root "index.php"), $landingPhp, $utf8NoBom)

Write-Step "Making the sidebar independently scrollable"

$aggressivePath = Join-Path $root "assets\css\aggressive.css"
if (-not (Test-Path $aggressivePath)) {
    [System.IO.File]::WriteAllText($aggressivePath, "", $utf8NoBom)
}

$aggressive = [System.IO.File]::ReadAllText($aggressivePath)

$sidebarPatchMarker = "/* PATCH-003: independent sidebar scrolling */"
if ($aggressive -notmatch [regex]::Escape($sidebarPatchMarker)) {
    $sidebarCss = @'

/* PATCH-003: independent sidebar scrolling */
.sidebar {
    height: 100vh !important;
    height: 100dvh !important;
    max-height: 100vh !important;
    max-height: 100dvh !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior-y: contain !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 72, 42, .48) rgba(255, 255, 255, .035);
}

.sidebar::-webkit-scrollbar {
    width: 7px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, .025);
}

.sidebar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #ff321d, #ff7900);
    border-radius: 999px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #ff513b, #ff8a1c);
}

.sidebar .brand {
    flex: 0 0 auto;
}

.sidebar .sidebar-section,
.sidebar .sidebar-nav {
    flex: 0 0 auto;
}

.sidebar .sidebar-bottom {
    flex: 0 0 auto;
    margin-top: 18px !important;
}

/* Explicit logout action in the authenticated application topbar */
.topbar-logout {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255, 74, 45, .25);
    border-radius: 7px;
    color: #ff6548;
    background: rgba(255, 42, 26, .055);
    transition: .18s ease;
}

.topbar-logout:hover {
    color: #fff;
    border-color: rgba(255, 74, 45, .52);
    background: rgba(255, 42, 26, .15);
    box-shadow: 0 0 24px rgba(255, 42, 26, .12);
}

@media (max-width: 900px) {
    .sidebar {
        height: 100dvh !important;
        max-height: 100dvh !important;
        overflow-y: auto !important;
        overscroll-behavior-y: contain !important;
    }
}
'@
    $aggressive += $sidebarCss
    [System.IO.File]::WriteAllText($aggressivePath, $aggressive, $utf8NoBom)
    Write-Host "[OK] Sidebar scroll CSS added" -ForegroundColor Green
} else {
    Write-Host "[SKIP] Sidebar scroll CSS already present" -ForegroundColor Yellow
}

Write-Step "Adding logout button to the landing-page visual style"

$landingCssPath = Join-Path $root "assets\css\landing.css"
if (Test-Path $landingCssPath) {
    $landingCss = [System.IO.File]::ReadAllText($landingCssPath)
    if ($landingCss -notmatch "\.nav-btn-danger") {
        $logoutLandingCss = @'

/* PATCH-003: authenticated landing logout */
.nav-btn-danger {
    color: #ff6d51;
    border-color: rgba(255, 74, 45, .30);
    background: rgba(255, 42, 26, .055);
    gap: 7px;
}
.nav-btn-danger:hover {
    color: #fff;
    border-color: rgba(255, 74, 45, .58);
    background: rgba(255, 42, 26, .15);
}
'@
        $landingCss += $logoutLandingCss
        [System.IO.File]::WriteAllText($landingCssPath, $landingCss, $utf8NoBom)
    }
}

Write-Step "Adding a visible logout button to the application topbar"

$headerPath = Join-Path $root "includes\header.php"
if (Test-Path $headerPath) {
    $header = [System.IO.File]::ReadAllText($headerPath)

    # Ensure app navigation treats dashboard.php as the dashboard, not public index.php.
    $header = $header.Replace('href="index.php"><span class="brand-mark"', 'href="dashboard.php"><span class="brand-mark"')
    $header = $header.Replace('href="index.php"><i class="bi bi-grid-1x2"></i>Dashboard', 'href="dashboard.php"><i class="bi bi-grid-1x2"></i>Dashboard')
    $header = $header.Replace("`$current==='index.php'", "`$current==='dashboard.php'")

    if ($header -notmatch 'class="topbar-logout"') {
        $profileNeedle = '<a class="profile-chip" href="profile.php?id=<?= e($user[''id'']) ?>"><span class="avatar"><?= e(strtoupper(substr($user[''username''],0,1))) ?></span><span><strong><?= e($user[''username'']) ?></strong><small><?= e($user[''rank'']) ?></small></span></a>'
        $profileReplacement = $profileNeedle + [Environment]::NewLine + '                <a class="topbar-logout" href="logout.php" title="Log out" aria-label="Log out"><i class="bi bi-box-arrow-right"></i></a>'

        if ($header.Contains($profileNeedle)) {
            $header = $header.Replace($profileNeedle, $profileReplacement)
        } else {
            # Fallback: insert immediately before the authenticated endif inside topbar actions.
            $fallback = '                <?php endif; ?>' + [Environment]::NewLine + '            </div>'
            $replacement = '                <a class="topbar-logout" href="logout.php" title="Log out" aria-label="Log out"><i class="bi bi-box-arrow-right"></i></a>' + [Environment]::NewLine + '                <?php endif; ?>' + [Environment]::NewLine + '            </div>'
            if ($header.Contains($fallback)) {
                $header = $header.Replace($fallback, $replacement)
            } else {
                Write-Warning "Could not locate topbar profile block automatically. Sidebar logout still remains available."
            }
        }
    }

    [System.IO.File]::WriteAllText($headerPath, $header, $utf8NoBom)
}

Write-Step "Verifying that index.php is public landing and dashboard.php remains separate"

$index = [System.IO.File]::ReadAllText((Join-Path $root "index.php"))
if ($index -notmatch 'class="forge-landing"') {
    throw "index.php verification failed: landing-page marker was not found."
}
if ($index -match 'require_login\s*\(') {
    throw "index.php verification failed: public landing page contains require_login()."
}
if (-not (Test-Path (Join-Path $root "dashboard.php"))) {
    throw "dashboard.php is missing. Backup is at: $backup"
}

Write-Host "[OK] index.php = public landing page" -ForegroundColor Green
Write-Host "[OK] dashboard.php = separate authenticated application entry" -ForegroundColor Green

Write-Step "Running PHP syntax checks"

$phpExe = "php"
$xamppPhp = "D:\xampp\php\php.exe"
if (Test-Path $xamppPhp) {
    $phpExe = $xamppPhp
}

$lintFailed = $false
foreach ($file in @("index.php","dashboard.php","includes\header.php","logout.php")) {
    $full = Join-Path $root $file
    if (Test-Path $full) {
        $output = & $phpExe -l $full 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[FAIL] $file" -ForegroundColor Red
            $output | ForEach-Object { Write-Host $_ }
            $lintFailed = $true
        } else {
            Write-Host "[OK]   $file" -ForegroundColor Green
        }
    }
}

if ($lintFailed) {
    Write-Host ""
    Write-Host "PATCH-003 MADE THE CHANGES, BUT PHP LINT FOUND AN ERROR." -ForegroundColor Yellow
    Write-Host "Backup: $backup" -ForegroundColor DarkGray
    exit 1
}

Write-Step "Patch complete"
Write-Host "PATCH-003 APPLIED SUCCESSFULLY" -ForegroundColor Green
Write-Host ""
Write-Host "Public landing: http://localhost/codeforge/" -ForegroundColor White
Write-Host "Dashboard:      http://localhost/codeforge/dashboard.php" -ForegroundColor White
Write-Host "Logout:         http://localhost/codeforge/logout.php" -ForegroundColor White
Write-Host ""
Write-Host "Sidebar now has its own vertical scroll and blocks wheel-scroll chaining to the main page." -ForegroundColor Cyan
Write-Host "Logged-in users now get an explicit Log out button on the landing page and app topbar." -ForegroundColor Cyan
Write-Host ""
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host "Use Ctrl + F5 once after opening the site." -ForegroundColor Yellow
