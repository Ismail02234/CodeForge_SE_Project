param(
    [string]$ProjectRoot = (Get-Location).Path
)

$ErrorActionPreference = 'Stop'
$PatchName = 'PATCH-001-aggressive-landing-auth'
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    $parent = Split-Path -Parent $Path
    if ($parent -and -not (Test-Path $parent)) {
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
    }
    [System.IO.File]::WriteAllText($Path, $Content, $Utf8NoBom)
}

function Read-All([string]$Path) {
    return [System.IO.File]::ReadAllText($Path)
}

$ProjectRoot = (Resolve-Path $ProjectRoot).Path
$Required = @(
    'core\bootstrap.php',
    'core\auth.php',
    'includes\header.php',
    'assets\css\app.css',
    'login.php',
    'index.php'
)

foreach ($item in $Required) {
    if (-not (Test-Path (Join-Path $ProjectRoot $item))) {
        throw "This does not look like the CodeForge project root. Missing: $item`nRun from D:\xampp\htdocs\codeforge or pass -ProjectRoot explicitly."
    }
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$BackupRoot = Join-Path $ProjectRoot "patches\backups\$PatchName-$timestamp"
New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null

$BackupFiles = @(
    'index.php',
    'dashboard.php',
    'login.php',
    'register.php',
    'logout.php',
    'core\auth.php',
    'includes\header.php',
    'assets\css\app.css',
    'assets\js\landing.js'
)

foreach ($relative in $BackupFiles) {
    $source = Join-Path $ProjectRoot $relative
    if (Test-Path $source) {
        $destination = Join-Path $BackupRoot $relative
        $destinationDir = Split-Path -Parent $destination
        if (-not (Test-Path $destinationDir)) {
            New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
        }
        Copy-Item $source $destination -Force
    }
}

Write-Host "[$PatchName] Backup created:" -ForegroundColor DarkGray
Write-Host "  $BackupRoot" -ForegroundColor DarkGray

# Preserve the current authenticated dashboard before index.php becomes the public landing page.
$DashboardPath = Join-Path $ProjectRoot 'dashboard.php'
if (-not (Test-Path $DashboardPath)) {
    $CurrentIndex = Read-All (Join-Path $ProjectRoot 'index.php')
    if ($CurrentIndex -notmatch 'require_login\s*\(') {
        throw 'Safety check failed: existing index.php is not the expected authenticated dashboard. Nothing was overwritten.'
    }
    Write-Utf8NoBom $DashboardPath $CurrentIndex
    Write-Host '[1/8] Existing dashboard preserved as dashboard.php' -ForegroundColor Green
} else {
    Write-Host '[1/8] dashboard.php already exists; preserving it.' -ForegroundColor Yellow
}

$Content_index_php = @'
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
    <title>CodeForge — Enter the Arena</title>
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

<span class="term-muted">// no spectators. ship the query.</span><span class="terminal-caret">█</span></code></pre>
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
'@
Write-Utf8NoBom (Join-Path $ProjectRoot 'index.php') $Content_index_php
Write-Host '[2/8] Public reactive landing page installed' -ForegroundColor Green

$Content_login_php = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$error = null;
$username = '';

if (request_method('POST')) {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string) $user['password'])) {
        login_user($user);
        $pdo->prepare("INSERT INTO activity_logs(user_id, action, details) VALUES(:uid, 'auth.login', 'Signed in')")
            ->execute(['uid' => $user['id']]);
        redirect('dashboard.php');
    }

    $error = 'Access denied. Check your username and password.';
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#050608">
    <title>Log In — CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<div class="auth-grid-bg" aria-hidden="true"></div>
<div class="auth-glow auth-glow-a" aria-hidden="true"></div>
<div class="auth-glow auth-glow-b" aria-hidden="true"></div>

<a class="auth-back" href="index.php"><i class="bi bi-arrow-left"></i> Back to landing</a>

<main class="auth-shell">
    <section class="auth-visual-panel">
        <a class="landing-brand" href="index.php">
            <span class="landing-brand-icon">CF</span>
            <span>CODE<span>FORGE</span></span>
        </a>
        <div class="auth-visual-copy">
            <div class="landing-kicker"><span></span> Operator authentication</div>
            <h1>RETURN TO<br><em>THE ARENA.</em></h1>
            <p>Your telemetry is waiting. Resume practice, continue races, and take your place in SQL Battle.</p>
        </div>
        <div class="auth-system-list">
            <span><i class="bi bi-hexagon-half"></i> CODE DNA // ONLINE</span>
            <span><i class="bi bi-ghost"></i> GHOST ENGINE // ONLINE</span>
            <span><i class="bi bi-database-fill-gear"></i> SQL ARENA // ONLINE</span>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="auth-form-inner">
            <div class="auth-step">AUTH // 01</div>
            <h2>LOG IN</h2>
            <p class="auth-subtitle">Existing operator access.</p>

            <?php if ($error): ?>
                <div class="auth-alert"><i class="bi bi-exclamation-octagon"></i><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($message = flash('success')): ?>
                <div class="auth-alert success"><i class="bi bi-check2-circle"></i><?= e($message) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-input-wrap"><i class="bi bi-person"></i><input id="username" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username" placeholder="Enter username"></div>
                </div>
                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap"><i class="bi bi-key"></i><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter password"></div>
                </div>
                <button class="btn btn-strike btn-lg btn-block" type="submit">AUTHENTICATE <i class="bi bi-arrow-right"></i></button>
            </form>

            <div class="auth-switch">New to CodeForge? <a href="register.php">Create an account</a></div>
            <details class="demo-credentials">
                <summary>Demo credentials</summary>
                <div>User: <span class="mono">Ismail / 123456</span><br>Admin: <span class="mono">Admin / admin123</span></div>
            </details>
        </div>
    </section>
</main>
</body>
</html>
'@
Write-Utf8NoBom (Join-Path $ProjectRoot 'login.php') $Content_login_php
Write-Host '[3/8] Aggressive login screen installed' -ForegroundColor Green

$Content_register_php = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$universities = $pdo->query('SELECT name FROM universities ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$error = null;
$username = '';
$university = '';

if (request_method('POST')) {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $university = trim((string) ($_POST['university'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,24}$/', $username)) {
        $error = 'Username must be 3–24 characters using letters, numbers or underscore.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } elseif ($university !== '' && !in_array($university, $universities, true)) {
        $error = 'Choose a valid university.';
    } else {
        $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $exists->execute(['username' => $username]);

        if ($exists->fetchColumn()) {
            $error = 'That username is already in use.';
        } else {
            $userId = generate_id('u');
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO users (id, username, password, role, rating, university, `rank`) VALUES (:id, :username, :password, 'user', 1200, :university, 'Newbie')");
                $stmt->execute([
                    'id' => $userId,
                    'username' => $username,
                    'password' => $passwordHash,
                    'university' => $university !== '' ? $university : null,
                ]);
                $pdo->prepare("INSERT INTO activity_logs(user_id, action, details) VALUES(:uid, 'auth.register', 'Account created')")
                    ->execute(['uid' => $userId]);
                $pdo->commit();

                login_user(['id' => $userId]);
                flash('success', 'Welcome to CodeForge. Your operator profile is now active.');
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
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#050608">
    <title>Create Account — CodeForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<div class="auth-grid-bg" aria-hidden="true"></div>
<div class="auth-glow auth-glow-a" aria-hidden="true"></div>
<div class="auth-glow auth-glow-b" aria-hidden="true"></div>

<a class="auth-back" href="index.php"><i class="bi bi-arrow-left"></i> Back to landing</a>

<main class="auth-shell auth-shell-register">
    <section class="auth-visual-panel">
        <a class="landing-brand" href="index.php">
            <span class="landing-brand-icon">CF</span>
            <span>CODE<span>FORGE</span></span>
        </a>
        <div class="auth-visual-copy">
            <div class="landing-kicker"><span></span> New operator protocol</div>
            <h1>BUILD YOUR<br><em>CODING IDENTITY.</em></h1>
            <p>Your account becomes the source for Code DNA, historical race telemetry, rankings and SQL Battle records.</p>
        </div>
        <div class="auth-system-list">
            <span><b>01</b> CREATE PROFILE</span>
            <span><b>02</b> RECORD ATTEMPTS</span>
            <span><b>03</b> EVOLVE YOUR DNA</span>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="auth-form-inner">
            <div class="auth-step">AUTH // 00</div>
            <h2>CREATE ACCOUNT</h2>
            <p class="auth-subtitle">New coder registration.</p>

            <?php if ($error): ?>
                <div class="auth-alert"><i class="bi bi-exclamation-octagon"></i><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form" novalidate>
                <?= csrf_field() ?>
                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-input-wrap"><i class="bi bi-person-plus"></i><input id="username" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username" maxlength="24" placeholder="Choose a username"></div>
                    <small>3–24 characters. Letters, numbers and underscore only.</small>
                </div>

                <div class="auth-field">
                    <label for="university">University</label>
                    <div class="auth-input-wrap"><i class="bi bi-mortarboard"></i><select id="university" name="university">
                        <option value="">No university selected</option>
                        <?php foreach ($universities as $name): ?>
                            <option value="<?= e($name) ?>" <?= $university === $name ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap"><i class="bi bi-key"></i><input id="password" type="password" name="password" required autocomplete="new-password" minlength="8" placeholder="Minimum 8 characters"></div>
                </div>

                <div class="auth-field">
                    <label for="confirm_password">Confirm password</label>
                    <div class="auth-input-wrap"><i class="bi bi-shield-check"></i><input id="confirm_password" type="password" name="confirm_password" required autocomplete="new-password" minlength="8" placeholder="Repeat password"></div>
                </div>

                <button class="btn btn-strike btn-lg btn-block" type="submit">CREATE OPERATOR <i class="bi bi-arrow-right"></i></button>
            </form>

            <div class="auth-switch">Already registered? <a href="login.php">Log in</a></div>
        </div>
    </section>
</main>
</body>
</html>
'@
Write-Utf8NoBom (Join-Path $ProjectRoot 'register.php') $Content_register_php
Write-Host '[4/8] Account registration flow installed' -ForegroundColor Green

$Content_assets_js_landing_js = @'
(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const canvas = document.getElementById('forgeCanvas');
  const aura = document.getElementById('cursorAura');
  const core = document.getElementById('landingCore');
  const ctx = canvas?.getContext('2d');

  const pointer = { x: window.innerWidth / 2, y: window.innerHeight / 2, active: false };
  let width = window.innerWidth;
  let height = window.innerHeight;
  let dpr = Math.min(window.devicePixelRatio || 1, 2);
  let particles = [];
  let raf = 0;

  const palette = ['255,60,41', '255,112,38', '24,218,255'];

  function resize() {
    if (!canvas || !ctx) return;
    width = window.innerWidth;
    height = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const count = Math.max(45, Math.min(110, Math.floor((width * height) / 18000)));
    particles = Array.from({ length: count }, (_, index) => ({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.28,
      vy: (Math.random() - 0.5) * 0.28,
      r: Math.random() * 1.45 + 0.45,
      c: palette[index % palette.length],
    }));
  }

  function render() {
    if (!ctx || reducedMotion) return;
    ctx.clearRect(0, 0, width, height);

    particles.forEach((p, i) => {
      const dx = pointer.x - p.x;
      const dy = pointer.y - p.y;
      const distance = Math.hypot(dx, dy) || 1;

      if (pointer.active && distance < 210) {
        const force = (210 - distance) / 210;
        p.vx -= (dx / distance) * force * 0.018;
        p.vy -= (dy / distance) * force * 0.018;
      }

      p.vx *= 0.995;
      p.vy *= 0.995;
      p.x += p.vx;
      p.y += p.vy;

      if (p.x < -20) p.x = width + 20;
      if (p.x > width + 20) p.x = -20;
      if (p.y < -20) p.y = height + 20;
      if (p.y > height + 20) p.y = -20;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${p.c}, .5)`;
      ctx.fill();

      for (let j = i + 1; j < particles.length; j += 1) {
        const q = particles[j];
        const lx = p.x - q.x;
        const ly = p.y - q.y;
        const d = Math.hypot(lx, ly);
        if (d < 108) {
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = `rgba(${p.c}, ${(1 - d / 108) * 0.12})`;
          ctx.lineWidth = 0.55;
          ctx.stroke();
        }
      }
    });

    if (pointer.active) {
      const glow = ctx.createRadialGradient(pointer.x, pointer.y, 0, pointer.x, pointer.y, 145);
      glow.addColorStop(0, 'rgba(255, 64, 35, .11)');
      glow.addColorStop(1, 'rgba(255, 64, 35, 0)');
      ctx.fillStyle = glow;
      ctx.beginPath();
      ctx.arc(pointer.x, pointer.y, 145, 0, Math.PI * 2);
      ctx.fill();
    }

    raf = requestAnimationFrame(render);
  }

  function movePointer(event) {
    pointer.x = event.clientX;
    pointer.y = event.clientY;
    pointer.active = true;
    if (aura) {
      aura.style.setProperty('--mx', `${event.clientX}px`);
      aura.style.setProperty('--my', `${event.clientY}px`);
      aura.classList.add('is-active');
    }
  }

  window.addEventListener('mousemove', movePointer, { passive: true });
  window.addEventListener('mouseleave', () => {
    pointer.active = false;
    aura?.classList.remove('is-active');
  });
  window.addEventListener('resize', resize, { passive: true });

  if (core && !reducedMotion) {
    core.closest('.landing-visual')?.addEventListener('mousemove', (event) => {
      const rect = core.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      core.style.transform = `perspective(900px) rotateX(${(-y * 13).toFixed(2)}deg) rotateY(${(x * 17).toFixed(2)}deg) translate3d(${(x * 12).toFixed(1)}px, ${(y * 9).toFixed(1)}px, 0)`;
    });
    core.closest('.landing-visual')?.addEventListener('mouseleave', () => {
      core.style.transform = '';
    });
  }

  document.querySelectorAll('[data-tilt-card]').forEach((card) => {
    if (reducedMotion) return;
    card.addEventListener('mousemove', (event) => {
      const rect = card.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `perspective(850px) rotateX(${(-y * 5).toFixed(2)}deg) rotateY(${(x * 7).toFixed(2)}deg) translateY(-4px)`;
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });

  document.querySelectorAll('[data-magnetic]').forEach((element) => {
    if (reducedMotion) return;
    element.addEventListener('mousemove', (event) => {
      const rect = element.getBoundingClientRect();
      const x = event.clientX - (rect.left + rect.width / 2);
      const y = event.clientY - (rect.top + rect.height / 2);
      element.style.transform = `translate(${x * 0.08}px, ${y * 0.08}px)`;
    });
    element.addEventListener('mouseleave', () => {
      element.style.transform = '';
    });
  });

  resize();
  if (!reducedMotion) render();
  window.addEventListener('beforeunload', () => cancelAnimationFrame(raf));
})();
'@
Write-Utf8NoBom (Join-Path $ProjectRoot 'assets\js\landing.js') $Content_assets_js_landing_js
Write-Host '[5/8] Mouse-reactive canvas / tilt animation installed' -ForegroundColor Green

$AggressiveCss = @'

/* ========================================================================== */
/* PATCH-001 — AGGRESSIVE VISUAL SYSTEM + LANDING / AUTH                     */
/* ========================================================================== */
:root{
  --bg:#050608;
  --bg2:#090b0e;
  --panel:#0d1014;
  --panel2:#11161c;
  --line:#252b32;
  --text:#f5f7fa;
  --muted:#8d969f;
  --cyan:#18dafa;
  --blue:#3387ff;
  --purple:#8a66ff;
  --green:#43e39d;
  --red:#ff3d29;
  --amber:#ff8a24;
  --strike:#ff3d29;
  --strike2:#ff7a21;
  --shadow:0 24px 80px rgba(0,0,0,.46);
}
html,body{background:radial-gradient(circle at 82% -10%,rgba(255,61,41,.10),transparent 31%),radial-gradient(circle at 4% 40%,rgba(24,218,250,.045),transparent 25%),#050608}
body{letter-spacing:-.01em}
::selection{background:rgba(255,61,41,.35);color:#fff}

/* Make the authenticated application more aggressive without changing markup. */
.sidebar{background:linear-gradient(180deg,#07090b,#090c10);border-right-color:#23282e}
.sidebar:before{content:"";position:absolute;left:0;top:0;bottom:0;width:2px;background:linear-gradient(180deg,var(--strike),transparent 40%,var(--cyan));opacity:.8}
.brand-mark{border-radius:5px;background:linear-gradient(135deg,var(--strike),var(--strike2));color:#fff;box-shadow:0 0 34px rgba(255,61,41,.22);transform:skewX(-6deg)}
.sidebar-nav a,.sidebar-bottom a{border-radius:4px}
.sidebar-nav a:hover,.sidebar-bottom a:hover{background:#12161b}
.sidebar-nav a.active{background:linear-gradient(90deg,rgba(255,61,41,.15),rgba(255,122,33,.035));border-color:rgba(255,61,41,.33);box-shadow:inset 2px 0 0 var(--strike)}
.sidebar-nav a.active i{color:var(--strike)}
.feature-link span{background:rgba(255,61,41,.12);color:#ff795f;border:1px solid rgba(255,61,41,.18)}
.topbar{background:rgba(5,6,8,.90);border-bottom-color:#23282e}
.global-search{background:#090c10;border-color:#262c33;border-radius:5px}
.card,.hero{background:linear-gradient(160deg,#0d1116,#090c10);border-color:#252b32;border-radius:7px}
.card:hover{border-color:#343b44}
.hero{border-left:2px solid var(--strike)}
.hero:after{background:radial-gradient(circle,rgba(255,61,41,.14),transparent 67%)}
.btn{border-radius:4px;text-transform:uppercase;letter-spacing:.055em}
.btn-primary{background:linear-gradient(135deg,var(--strike),var(--strike2));color:#fff;box-shadow:0 8px 30px rgba(255,61,41,.16)}
.btn-primary:hover{box-shadow:0 12px 38px rgba(255,61,41,.28)}
.progress span{background:linear-gradient(90deg,var(--strike),var(--strike2))}
.feature-icon{border-radius:4px;background:rgba(255,61,41,.09);color:#ff705d;border:1px solid rgba(255,61,41,.16)}
.eyebrow,.text-cyan{color:#ff6954}
.callout{border-left-color:var(--strike);background:rgba(255,61,41,.045)}
.form-control,.form-select,.input,.select,.textarea{border-radius:4px;background:#080b0f;border-color:#293039}

/* Shared landing/auth primitives. */
.landing-page,.auth-page{min-height:100vh;background:#050608;color:#f4f6f8;font-family:Inter,system-ui,sans-serif;overflow-x:hidden}
.landing-page{position:relative}
.landing-page main,.landing-nav,.landing-footer{position:relative;z-index:3}
.forge-canvas{position:fixed;inset:0;width:100%;height:100%;z-index:0;pointer-events:none;opacity:.95}
.scanlines{position:fixed;inset:0;pointer-events:none;z-index:2;opacity:.10;background:repeating-linear-gradient(to bottom,transparent 0,transparent 4px,rgba(255,255,255,.018) 5px)}
.cursor-aura{--mx:50vw;--my:50vh;position:fixed;inset:0;pointer-events:none;z-index:1;opacity:0;background:radial-gradient(circle 240px at var(--mx) var(--my),rgba(255,61,41,.08),transparent 67%);transition:opacity .25s}.cursor-aura.is-active{opacity:1}
.landing-brand{display:inline-flex;align-items:center;gap:11px;font-family:Orbitron,Inter,sans-serif;font-weight:900;letter-spacing:.02em}.landing-brand>span:last-child>span{color:var(--strike)}
.landing-brand-icon{width:38px;height:38px;display:grid;place-items:center;background:var(--strike);color:#fff;font:800 11px/1 Orbitron,sans-serif;clip-path:polygon(0 0,84% 0,100% 22%,100% 100%,16% 100%,0 78%);box-shadow:0 0 24px rgba(255,61,41,.25)}
.landing-kicker{display:flex;align-items:center;gap:10px;color:#9da4ab;font:700 10px/1.2 JetBrains Mono,monospace;letter-spacing:.13em;text-transform:uppercase}.landing-kicker>span{width:34px;height:2px;background:var(--strike);box-shadow:0 0 12px rgba(255,61,41,.55)}
.btn-ghost{background:rgba(255,255,255,.018);border-color:#30363d;color:#e7eaed}.btn-ghost:hover{border-color:#69717a;background:rgba(255,255,255,.045)}
.btn-strike{position:relative;background:linear-gradient(110deg,var(--strike),#ff6f1f);color:#fff;border-color:transparent;box-shadow:0 12px 36px rgba(255,61,41,.19);clip-path:polygon(0 0,calc(100% - 11px) 0,100% 11px,100% 100%,11px 100%,0 calc(100% - 11px))}.btn-strike:hover{box-shadow:0 14px 44px rgba(255,61,41,.36);filter:saturate(1.12)}
.btn-lg{padding:14px 19px;font-size:12px}.btn-block{width:100%}

/* Landing navigation. */
.landing-nav{height:86px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:30px;max-width:1520px;margin:0 auto;padding:0 42px;border-bottom:1px solid rgba(255,255,255,.07)}
.landing-links{display:flex;justify-content:center;gap:30px}.landing-links a{position:relative;color:#7f8790;font:700 10px/1 JetBrains Mono,monospace;letter-spacing:.12em;text-transform:uppercase}.landing-links a:hover{color:#fff}.landing-links a:after{content:"";position:absolute;left:0;right:100%;bottom:-10px;height:2px;background:var(--strike);transition:.2s}.landing-links a:hover:after{right:0}
.landing-auth-actions{display:flex;align-items:center;gap:8px}

/* Landing hero. */
.landing-hero{min-height:calc(100vh - 86px);max-width:1520px;margin:0 auto;padding:72px 42px 70px;display:grid;grid-template-columns:minmax(0,1.08fr) minmax(420px,.92fr);align-items:center;gap:40px;position:relative}
.hero-noise{position:absolute;inset:0;pointer-events:none;opacity:.18;background-image:linear-gradient(90deg,transparent 49.8%,rgba(255,255,255,.025) 50%,transparent 50.2%),linear-gradient(0deg,transparent 49.8%,rgba(255,255,255,.018) 50%,transparent 50.2%);background-size:90px 90px;mask-image:linear-gradient(to bottom,black,transparent 88%)}
.hero-copy{position:relative;z-index:2}.hero-copy h1{margin:21px 0 22px;font:900 clamp(60px,8vw,124px)/.78 Orbitron,Inter,sans-serif;letter-spacing:-.075em;text-transform:uppercase}.hero-copy h1 span{display:block}.hero-copy h1 span:nth-child(2){margin-left:.15em;color:transparent;-webkit-text-stroke:1.5px rgba(245,247,250,.55)}.hero-copy h1 .impact-word{color:var(--strike);text-shadow:0 0 50px rgba(255,61,41,.20)}
.landing-lead{max-width:720px;color:#9ba3ab;font-size:clamp(15px,1.45vw,20px);line-height:1.7;margin:0}.landing-cta-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:31px}.hero-status-line{display:flex;flex-wrap:wrap;gap:19px;margin-top:30px;color:#6f777f;font:600 9px/1 JetBrains Mono,monospace;letter-spacing:.09em}.hero-status-line span:first-child{color:#94a09b}.hero-status-line i{display:inline-block;width:6px;height:6px;border-radius:50%;margin-right:7px;background:#3ee38e;box-shadow:0 0 13px #3ee38e;animation:statusPulse 1.8s infinite}
@keyframes statusPulse{50%{opacity:.35;transform:scale(.8)}}

/* Reactive core. */
.landing-visual{min-height:580px;display:grid;place-items:center;position:relative;perspective:900px}.landing-core{position:relative;width:min(470px,42vw);aspect-ratio:1;display:grid;place-items:center;transition:transform .12s ease-out;transform-style:preserve-3d}.landing-core:before{content:"";position:absolute;inset:8%;border:1px solid rgba(255,61,41,.11);background:radial-gradient(circle,rgba(255,61,41,.10),rgba(0,0,0,.04) 40%,transparent 68%);clip-path:polygon(50% 0,94% 25%,94% 75%,50% 100%,6% 75%,6% 25%)}
.core-ring{position:absolute;border:1px solid}.ring-a{inset:4%;border-color:rgba(255,61,41,.30);border-radius:50%;animation:spinCore 17s linear infinite}.ring-a:before,.ring-a:after{content:"";position:absolute;width:11px;height:11px;background:var(--strike);box-shadow:0 0 20px rgba(255,61,41,.8)}.ring-a:before{left:9%;top:18%}.ring-a:after{right:8%;bottom:20%}.ring-b{inset:15%;border-radius:50%;border-style:dashed;border-color:rgba(24,218,250,.28);animation:spinCoreReverse 13s linear infinite}.ring-c{inset:28%;border-color:rgba(255,255,255,.12);transform:rotate(45deg);animation:ringBreath 3s ease-in-out infinite}
.core-cross{position:absolute;inset:48% 2%;height:1px;background:linear-gradient(90deg,transparent,rgba(255,61,41,.35),transparent)}.cross-b{transform:rotate(90deg)}
.core-center{width:150px;height:150px;display:grid;place-content:center;text-align:center;background:#080a0d;border:1px solid rgba(255,61,41,.38);clip-path:polygon(50% 0,93% 24%,93% 76%,50% 100%,7% 76%,7% 24%);box-shadow:0 0 75px rgba(255,61,41,.16),inset 0 0 34px rgba(255,61,41,.05);transform:translateZ(45px)}.core-center span{color:var(--strike);font:700 30px/1 JetBrains Mono,monospace}.core-center strong{margin-top:8px;font:800 15px/1 Orbitron,sans-serif;letter-spacing:.12em}.core-center small{margin-top:7px;color:#707880;font:600 8px/1 JetBrains Mono,monospace;letter-spacing:.12em}
.core-node{position:absolute;padding:8px 10px;background:#090c10;border:1px solid #30363d;color:#aeb6bd;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.08em;box-shadow:0 10px 30px rgba(0,0,0,.25)}.node-a{left:0;top:28%;border-left:2px solid var(--strike)}.node-b{right:-2%;top:42%;border-right:2px solid var(--cyan)}.node-c{left:18%;bottom:4%;border-left:2px solid var(--strike2)}
.visual-caption{position:absolute;bottom:4%;color:#5f676f;font:600 8px/1 JetBrains Mono,monospace;letter-spacing:.15em}
@keyframes spinCore{to{transform:rotate(360deg)}}@keyframes spinCoreReverse{to{transform:rotate(-360deg)}}@keyframes ringBreath{50%{opacity:.45;transform:rotate(45deg) scale(.94)}}

/* Landing stats + sections. */
.landing-stats{position:relative;z-index:3;max-width:1520px;margin:0 auto 110px;padding:0 42px;display:grid;grid-template-columns:repeat(4,1fr)}.landing-stats article{min-height:112px;padding:25px;border:1px solid #242a30;border-right:0;background:rgba(8,10,13,.75)}.landing-stats article:last-child{border-right:1px solid #242a30}.landing-stats strong{display:block;color:#f4f6f8;font:800 30px/1 Orbitron,sans-serif}.landing-stats article:first-child strong,.landing-stats article:nth-child(3) strong{color:var(--strike)}.landing-stats span{display:block;margin-top:11px;color:#686f77;font:600 8px/1 JetBrains Mono,monospace;letter-spacing:.12em}
.landing-section{position:relative;z-index:3;max-width:1520px;margin:0 auto;padding:0 42px 130px}.section-rail{display:flex;align-items:center;gap:12px;border-bottom:1px solid #22272d;padding-bottom:12px;margin-bottom:34px;color:#697078;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.12em}.section-rail span{color:var(--strike)}
.landing-section-head{display:grid;grid-template-columns:1fr minmax(320px,.72fr);gap:60px;align-items:end;margin-bottom:38px}.landing-section h2,.landing-final-cta h2{margin:14px 0 0;font:900 clamp(38px,5vw,70px)/.94 Orbitron,Inter,sans-serif;letter-spacing:-.055em}.landing-section-head>p,.combat-copy>p{color:#8c949c;line-height:1.8;margin:0;max-width:650px}
.weapon-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.weapon-card{position:relative;min-height:360px;padding:28px;background:linear-gradient(155deg,#0c0f13,#080a0d);border:1px solid #252b31;overflow:hidden;transition:transform .15s ease-out,border-color .2s,box-shadow .2s;transform-style:preserve-3d}.weapon-card:after{content:"";position:absolute;right:-70px;bottom:-80px;width:190px;height:190px;border:1px solid rgba(255,255,255,.06);transform:rotate(45deg)}.weapon-card:hover{border-color:#3b424a;box-shadow:0 30px 70px rgba(0,0,0,.3)}.weapon-card>i{display:block;margin:52px 0 25px;color:var(--strike);font-size:43px}.weapon-sql>i{color:var(--cyan)}.weapon-ghost>i{color:#ff8b38}.weapon-card h3{margin:0;font:800 25px/1 Orbitron,sans-serif;letter-spacing:-.035em}.weapon-card p{color:#858e96;line-height:1.75;font-size:13px;margin:15px 0 30px}.weapon-index{color:#555d65;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.12em}.weapon-tag{position:absolute;left:28px;bottom:25px;color:#69727a;font:700 8px/1 JetBrains Mono,monospace;letter-spacing:.12em}.weapon-dna{box-shadow:inset 0 2px 0 rgba(255,61,41,.65)}.weapon-ghost{box-shadow:inset 0 2px 0 rgba(255,139,56,.6)}.weapon-sql{box-shadow:inset 0 2px 0 rgba(24,218,250,.55)}
.systems-section{padding-bottom:150px}.combat-grid{display:grid;grid-template-columns:.88fr 1.12fr;gap:70px;align-items:center}.combat-copy h2{margin-bottom:24px}.text-strike{display:inline-flex;align-items:center;gap:9px;margin-top:25px;color:#ff6c57;font:800 10px/1 JetBrains Mono,monospace;letter-spacing:.1em}.terminal-rig{background:#07090b;border:1px solid #293039;box-shadow:0 30px 80px rgba(0,0,0,.38);transform:skewY(-1deg)}.terminal-top{height:42px;display:flex;align-items:center;gap:7px;padding:0 14px;border-bottom:1px solid #22282f}.terminal-top span{width:7px;height:7px;border-radius:50%;background:#444b52}.terminal-top span:first-child{background:var(--strike)}.terminal-top b{margin-left:auto;color:#5e676f;font:600 8px/1 JetBrains Mono,monospace}.terminal-rig pre{margin:0;padding:30px;min-height:290px;white-space:pre-wrap;color:#aeb7bf;font:500 12px/2 JetBrains Mono,monospace}.terminal-rig b{color:#f3f5f7}.term-muted{color:#59626b}.term-red{color:#ff654e}.term-orange{color:#ff9b4a}.term-cyan{color:#4bddf4}.terminal-caret{color:var(--strike);animation:blinkCaret .8s steps(1) infinite}@keyframes blinkCaret{50%{opacity:0}}
.landing-final-cta{position:relative;z-index:3;max-width:1436px;margin:0 auto 100px;padding:58px;display:flex;align-items:end;justify-content:space-between;gap:30px;border:1px solid #2b3138;background:linear-gradient(110deg,rgba(255,61,41,.075),rgba(8,10,13,.92) 38%);clip-path:polygon(0 0,96% 0,100% 24%,100% 100%,4% 100%,0 76%)}.landing-final-cta h2 em{color:var(--strike);font-style:normal}.final-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}.landing-footer{max-width:1520px;margin:0 auto;padding:28px 42px 40px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;border-top:1px solid #22272d;color:#596169;font:600 8px/1 JetBrains Mono,monospace;letter-spacing:.1em}.landing-footer>span:last-child{text-align:right}.landing-footer .landing-brand{font-size:13px}.landing-footer .landing-brand-icon{width:28px;height:28px;font-size:8px}

/* Authentication. */
.auth-page{display:grid;place-items:center;padding:44px;position:relative;overflow:hidden}.auth-grid-bg{position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.023) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.023) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(circle at center,black,transparent 75%)}.auth-glow{position:fixed;width:520px;height:520px;border-radius:50%;filter:blur(120px);opacity:.10;pointer-events:none}.auth-glow-a{left:-180px;top:-180px;background:var(--strike)}.auth-glow-b{right:-220px;bottom:-250px;background:var(--cyan)}.auth-back{position:fixed;left:28px;top:25px;z-index:5;display:flex;gap:8px;color:#7b838b;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.08em;text-transform:uppercase}.auth-back:hover{color:#fff}.auth-shell{position:relative;z-index:2;width:min(1110px,100%);min-height:650px;display:grid;grid-template-columns:1.03fr .97fr;background:#090c10;border:1px solid #2c333a;box-shadow:0 40px 110px rgba(0,0,0,.48);clip-path:polygon(0 0,97% 0,100% 5%,100% 100%,3% 100%,0 95%)}.auth-shell-register{min-height:720px}.auth-visual-panel{position:relative;overflow:hidden;padding:45px;background:linear-gradient(145deg,#0d1116,#07090c);border-right:1px solid #272d34;display:flex;flex-direction:column}.auth-visual-panel:after{content:"";position:absolute;width:410px;height:410px;right:-180px;bottom:-180px;border:1px solid rgba(255,61,41,.18);transform:rotate(45deg);box-shadow:0 0 70px rgba(255,61,41,.04)}.auth-visual-copy{margin:auto 0}.auth-visual-copy h1{margin:17px 0 18px;font:900 clamp(42px,4.5vw,67px)/.92 Orbitron,sans-serif;letter-spacing:-.06em}.auth-visual-copy h1 em{color:var(--strike);font-style:normal}.auth-visual-copy p{max-width:450px;color:#8b949c;line-height:1.75;font-size:13px}.auth-system-list{display:grid;gap:10px;position:relative;z-index:2}.auth-system-list span{padding:10px 12px;border-left:2px solid #353c43;background:rgba(255,255,255,.018);color:#747d86;font:600 9px/1 JetBrains Mono,monospace;letter-spacing:.08em}.auth-system-list i{color:#ff6b55;margin-right:8px}.auth-system-list b{display:inline-block;color:var(--strike);min-width:25px}.auth-form-panel{display:grid;place-items:center;padding:48px}.auth-form-inner{width:min(390px,100%)}.auth-step{color:#ff654e;font:700 9px/1 JetBrains Mono,monospace;letter-spacing:.12em;margin-bottom:11px}.auth-form-inner h2{margin:0;font:900 35px/1 Orbitron,sans-serif;letter-spacing:-.045em}.auth-subtitle{margin:10px 0 27px;color:#707982;font-size:12px}.auth-form{display:grid;gap:15px}.auth-field{display:grid;gap:7px}.auth-field label{color:#8c959e;font:700 9px/1 JetBrains Mono,monospace;text-transform:uppercase;letter-spacing:.09em}.auth-field small{color:#596169;font-size:9px;line-height:1.4}.auth-input-wrap{height:49px;display:flex;align-items:center;background:#07090c;border:1px solid #2a3138;transition:.18s;position:relative}.auth-input-wrap:focus-within{border-color:rgba(255,61,41,.7);box-shadow:0 0 0 3px rgba(255,61,41,.07)}.auth-input-wrap>i{width:44px;text-align:center;color:#626b74}.auth-input-wrap input,.auth-input-wrap select{height:100%;min-width:0;flex:1;border:0;outline:0;background:transparent;color:#f1f3f5;padding:0 13px 0 0;font:500 13px Inter,sans-serif}.auth-input-wrap select{appearance:none}.auth-input-wrap option{background:#0b0e12;color:#fff}.auth-input-wrap input::placeholder{color:#4e565e}.auth-alert{display:flex;gap:9px;align-items:flex-start;margin:0 0 17px;padding:11px 12px;border-left:2px solid var(--strike);background:rgba(255,61,41,.07);color:#ff8d7e;font-size:11px;line-height:1.5}.auth-alert.success{border-color:#41dc96;background:rgba(65,220,150,.07);color:#8be6b9}.auth-switch{text-align:center;margin-top:20px;color:#666f78;font-size:11px}.auth-switch a{color:#ff6f59;font-weight:800}.demo-credentials{margin-top:18px;border-top:1px solid #242a30;padding-top:13px;color:#59616a;font-size:9px}.demo-credentials summary{cursor:pointer;color:#767f88;margin-bottom:8px}.demo-credentials div{line-height:1.65}

@media(max-width:1100px){
  .landing-hero{grid-template-columns:1fr;min-height:auto;padding-top:70px}.landing-visual{min-height:480px}.landing-core{width:min(440px,72vw)}.weapon-grid{grid-template-columns:1fr}.weapon-card{min-height:290px}.combat-grid{grid-template-columns:1fr}.auth-shell{grid-template-columns:.86fr 1.14fr}.auth-visual-panel{padding:34px}.auth-visual-copy h1{font-size:46px}
}
@media(max-width:820px){
  .landing-nav{padding:0 20px}.landing-links{display:none}.landing-auth-actions .btn-ghost{display:none}.landing-hero,.landing-section,.landing-stats{padding-left:20px;padding-right:20px}.landing-hero{padding-top:55px}.hero-copy h1{font-size:clamp(55px,15vw,90px)}.landing-stats{grid-template-columns:repeat(2,1fr)}.landing-stats article:nth-child(2){border-right:1px solid #242a30}.landing-stats article:nth-child(-n+2){border-bottom:0}.landing-section-head{grid-template-columns:1fr;gap:22px}.landing-final-cta{margin:0 20px 70px;padding:38px;display:grid}.final-actions{justify-content:flex-start}.landing-footer{padding-left:20px;padding-right:20px;grid-template-columns:1fr auto}.landing-footer>span:nth-child(2){display:none}.auth-page{padding:66px 18px 24px}.auth-shell{grid-template-columns:1fr;clip-path:none}.auth-visual-panel{display:none}.auth-form-panel{padding:42px 26px}.auth-shell-register{min-height:auto}
}
@media(max-width:540px){
  .landing-nav{height:74px}.landing-brand{font-size:13px}.landing-brand-icon{width:33px;height:33px}.landing-auth-actions .btn{padding:10px 11px;font-size:9px}.landing-hero{min-height:auto}.hero-copy h1{font-size:clamp(50px,16vw,72px);line-height:.82}.landing-lead{font-size:14px}.landing-cta-row{display:grid}.landing-cta-row .btn{width:100%}.landing-visual{min-height:380px}.landing-core{width:86vw}.core-center{width:120px;height:120px}.visual-caption{bottom:0}.landing-stats{margin-bottom:80px}.landing-stats article{padding:20px 16px}.landing-stats strong{font-size:23px}.landing-section{padding-bottom:90px}.landing-section h2,.landing-final-cta h2{font-size:38px}.weapon-card{padding:22px}.weapon-card>i{margin-top:35px}.terminal-rig pre{padding:20px;font-size:10px}.landing-final-cta{padding:30px 25px;clip-path:none}.final-actions{display:grid}.landing-footer{grid-template-columns:1fr}.landing-footer>span{display:none}.auth-form-panel{padding:35px 20px}.auth-form-inner h2{font-size:29px}
}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important}.forge-canvas{display:none}}
'@

$CssPath = Join-Path $ProjectRoot 'assets\css\app.css'
$ExistingCss = Read-All $CssPath
if ($ExistingCss -notmatch 'PATCH-001 — AGGRESSIVE VISUAL SYSTEM') {
    Write-Utf8NoBom $CssPath ($ExistingCss.TrimEnd() + "`r`n" + $AggressiveCss + "`r`n")
    Write-Host '[6/8] Aggressive global visual system appended' -ForegroundColor Green
} else {
    Write-Host '[6/8] Aggressive CSS marker already exists; skipped duplicate append' -ForegroundColor Yellow
}

# Point authenticated navigation directly to dashboard.php.
$HeaderPath = Join-Path $ProjectRoot 'includes\header.php'
$Header = Read-All $HeaderPath
$Header = $Header.Replace('href="index.php"', 'href="dashboard.php"')
$Header = $Header.Replace('$current===' + "'index.php'", '$current===' + "'dashboard.php'")
Write-Utf8NoBom $HeaderPath $Header

# Unauthorized admin redirects should return to the authenticated dashboard.
$AuthPath = Join-Path $ProjectRoot 'core\auth.php'
$Auth = Read-All $AuthPath
$Auth = $Auth.Replace("redirect('index.php');", "redirect('dashboard.php');")
Write-Utf8NoBom $AuthPath $Auth

# Logging out now returns to the public landing page.
$LogoutContent = @'
<?php
require_once __DIR__ . '/core/bootstrap.php';
logout_user();
redirect('index.php');
'@
Write-Utf8NoBom (Join-Path $ProjectRoot 'logout.php') $LogoutContent
Write-Host '[7/8] Navigation and logout flow rewired' -ForegroundColor Green

# Syntax-check the PHP files touched by this patch.
$Php = $null
$XamppPhp = 'D:\xampp\php\php.exe'
if (Test-Path $XamppPhp) {
    $Php = $XamppPhp
} else {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { $Php = $cmd.Source }
}

if ($Php) {
    $LintFiles = @(
        'index.php',
        'dashboard.php',
        'login.php',
        'register.php',
        'logout.php',
        'core\auth.php',
        'includes\header.php'
    )
    foreach ($relative in $LintFiles) {
        $target = Join-Path $ProjectRoot $relative
        $lintOutput = & $Php -l $target 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host $lintOutput -ForegroundColor Red
            throw "PHP syntax check failed for $relative. Your backup is safe at $BackupRoot"
        }
    }
    Write-Host '[8/8] PHP syntax checks passed' -ForegroundColor Green
} else {
    Write-Host '[8/8] PHP executable not found; syntax check skipped' -ForegroundColor Yellow
}

# Final sanity assertions.
if (-not (Test-Path (Join-Path $ProjectRoot 'register.php'))) { throw 'register.php was not created.' }
if (-not (Test-Path (Join-Path $ProjectRoot 'assets\js\landing.js'))) { throw 'landing.js was not created.' }
if ((Read-All (Join-Path $ProjectRoot 'index.php')) -notmatch 'forgeCanvas') { throw 'Landing page verification failed.' }
if ((Read-All $CssPath) -notmatch 'PATCH-001 — AGGRESSIVE VISUAL SYSTEM') { throw 'CSS verification failed.' }

Write-Host ''
Write-Host 'PATCH APPLIED SUCCESSFULLY' -ForegroundColor Cyan
Write-Host '------------------------------------------------------------' -ForegroundColor DarkGray
Write-Host 'Landing:        http://localhost/codeforge/' -ForegroundColor White
Write-Host 'Create account: http://localhost/codeforge/register.php' -ForegroundColor White
Write-Host 'Log in:         http://localhost/codeforge/login.php' -ForegroundColor White
Write-Host 'Dashboard:      http://localhost/codeforge/dashboard.php' -ForegroundColor White
Write-Host ''
Write-Host 'No database migration was required for this patch.' -ForegroundColor DarkGray
Write-Host "Rollback backup: $BackupRoot" -ForegroundColor DarkGray
