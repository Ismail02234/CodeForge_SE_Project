<?php
session_start();
require_once 'config/db.php'; // PDO connection

// 🔒 Security: only authenticated admins may run this migration
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$stmt = $pdo->prepare("SELECT role FROM Users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$guard = $stmt->fetch();
if (!$guard || $guard['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

try {
    // 1. Fetch all users
    $stmt = $pdo->query("SELECT id, password FROM Users");
    $users = $stmt->fetchAll();

    $updatedCount = 0;

    foreach ($users as $user) {
        $currentPass = $user['password'];

        // Skip if already hashed (password_hash() format starts with $2y$)
        if (str_starts_with($currentPass, '$2y$')) continue;

        // Hash the plaintext password
        $hashed = password_hash($currentPass, PASSWORD_DEFAULT);

        // Update in the database
        $updateStmt = $pdo->prepare("UPDATE Users SET password = :password WHERE id = :id");
        $updateStmt->execute([
            ':password' => $hashed,
            ':id' => $user['id']
        ]);

        $updatedCount++;
    }

    echo "Migration Complete! $updatedCount passwords hashed successfully.";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
