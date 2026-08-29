<?php
// config/db.php

// Load environment variables from .env (if exists)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    $DB_HOST = $env['DB_HOST'] ?? '127.0.0.1';
    $DB_PORT = $env['DB_PORT'] ?? '3307';
    $DB_NAME = $env['DB_NAME'] ?? 'project';
    $DB_USER = $env['DB_USER'] ?? 'root';
    $DB_PASS = $env['DB_PASS'] ?? '';
} else {
    // fallback defaults
    $DB_HOST = '127.0.0.1';
    $DB_PORT = '3307';
    $DB_NAME = 'project';
    $DB_USER = 'root';
    $DB_PASS = '';
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
