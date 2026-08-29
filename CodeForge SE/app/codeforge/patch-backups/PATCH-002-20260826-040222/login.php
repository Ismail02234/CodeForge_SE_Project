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
    <title>Log In â€” CodeForge</title>
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