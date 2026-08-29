<?php
// We don't call session_start() here because it's usually called in the main page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM Users WHERE id = ?");
$stmt->execute([$uId]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    // If not admin, kick them to the dashboard with a warning
    header("Location: index.php?error=unauthorized");
    exit();
}
?>
