<?php
require_once __DIR__ . '/core/bootstrap.php';
if (current_user($pdo)) redirect('index.php');
$error = null;
if (request_method('POST')) {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, (string)$user['password'])) {
        login_user($user);
        $pdo->prepare("INSERT INTO activity_logs(user_id,action,details) VALUES(:uid,'auth.login','Signed in')")->execute(['uid'=>$user['id']]);
        redirect('index.php');
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · CodeForge</title><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></head><body class="login-page">
<div class="login-shell">
<section class="login-visual"><a class="brand" href="login.php"><span class="brand-mark">&lt;/&gt;</span><span>CodeForge</span></a><h1>Compete.<br>Analyze.<br><span class="text-cyan">Evolve.</span></h1><p>A modern competitive-programming laboratory built to demonstrate real database design, analytics, gamification, and secure SQL evaluation.</p><div class="login-features"><span><i class="bi bi-hexagon"></i>Code DNA performance intelligence</span><span><i class="bi bi-ghost"></i>Historical Ghost Race engine</span><span><i class="bi bi-database-gear"></i>Read-only SQL Battle sandbox</span></div></section>
<section class="login-form"><div class="eyebrow">Welcome back</div><h2>Sign in to CodeForge</h2><p>Use a seeded account after importing <code>database/schema_and_seed.sql</code>.</p><?php if($error):?><div class="toast-banner danger"><i class="bi bi-exclamation-triangle"></i><?=e($error)?></div><?php endif;?>
<form method="post"><?=csrf_field()?><div class="field"><label class="form-label">Username</label><input class="form-control" name="username" required autofocus placeholder="Ismail"></div><div class="field"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required placeholder="••••••••"></div><button class="btn btn-primary" type="submit"><i class="bi bi-arrow-right-circle"></i>Sign in</button></form>
<div class="credentials"><strong>Demo accounts</strong><br>User: <span class="mono">Ismail / 123456</span><br>Admin: <span class="mono">Admin / admin123</span></div></section></div></body></html>
