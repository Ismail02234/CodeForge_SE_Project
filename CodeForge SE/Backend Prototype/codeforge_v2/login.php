<?php 
session_start();
require_once 'config/db.php'; // PDO connection
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // PDO Prepared Statement
    $stmt = $pdo->prepare("SELECT id, username, password FROM Users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CodeForge - Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; margin: auto; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="card login-card shadow-lg border-0">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                <h2 class="fw-bold mt-2">CodeForge</h2>
                <p class="text-muted">Secure Access Portal</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small text-center"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
