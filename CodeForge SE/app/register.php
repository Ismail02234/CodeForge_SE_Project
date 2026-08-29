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
    } elseif (strlen($password) < 8 || strlen($password) > 4096) {
        $error = 'Password must contain 8–4096 characters.';
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
                    <input type="password" name="password" maxlength="4096" required autocomplete="new-password" placeholder="Minimum 8 characters">
                </label>
                <label>
                    <span>CONFIRM</span>
                    <input type="password" name="confirm_password" maxlength="4096" required autocomplete="new-password" placeholder="Repeat password">
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