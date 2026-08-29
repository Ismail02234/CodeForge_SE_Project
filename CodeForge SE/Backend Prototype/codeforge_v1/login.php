<?php 
session_start();
require_once 'config/db.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, username, password FROM Users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeForge - Begin Your Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { 
            background: var(--bg);
            min-height: 100vh; 
            display: flex; 
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .login-card { 
            max-width: 440px; 
            width: 100%; 
            margin: auto; 
            border-radius: var(--radius-lg); 
            position: relative;
            z-index: 2;
        }
        .mascot-bg {
            position: absolute;
            font-size: 15rem;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }
        .mascot-1 { top: -5%; right: -5%; }
        .mascot-2 { bottom: -5%; left: -5%; animation-delay: 3s; }
    </style>
</head>
<body class="bg-light">
    <div class="mascot-bg mascot-1">🧙‍♂️</div>
    <div class="mascot-bg mascot-2">⚡</div>
    
    <div class="card login-card shadow-lg border-0 bounce-in">
        <div class="card-body p-5">
            <div class="text-center mb-5">
                <div class="mascot mb-3">🚀</div>
                <h2 class="fw-bold text-primary">CodeForge</h2>
                <p class="text-muted fs-5">Begin Your Coding Adventure!</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small text-center border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">
                        <i class="bi bi-person me-1"></i> Hero Name
                    </label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter your hero name...">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">
                        <i class="bi bi-key me-1"></i> Secret Password
                    </label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter your secret password...">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                    <i class="bi bi-door-open me-2"></i> Start Adventure
                </button>
            </form>
        </div>
    </div>
</body>
</html>
