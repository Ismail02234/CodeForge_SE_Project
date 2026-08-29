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
        $error = 'Username must be 3â€“24 characters using letters, numbers or underscore.';
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
    <title>Create Account â€” CodeForge</title>
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
                    <small>3â€“24 characters. Letters, numbers and underscore only.</small>
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