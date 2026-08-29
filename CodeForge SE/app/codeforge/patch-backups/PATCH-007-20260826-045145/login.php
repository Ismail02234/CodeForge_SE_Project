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
    if (strlen($password) > 4096) {
        $password = '';
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string) $user['password'])) {
        if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
            try {
                $rehash = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $rehash->execute([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $user['id'],
                ]);
            } catch (Throwable) {
                // Rehashing is an optimization and must never block sign-in.
            }
        }

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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/aggressive.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/polish.css')) ?>">
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
                <input type="password" name="password" maxlength="4096" required autocomplete="current-password" placeholder="Your password">
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