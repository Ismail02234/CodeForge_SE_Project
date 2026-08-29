<?php

declare(strict_types=1);

$envFile = __DIR__ . '/.env';
$env = file_exists($envFile) ? (parse_ini_file($envFile) ?: []) : [];

$DB_HOST = (string)($env['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
$DB_PORT = (string)($env['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
$DB_NAME = (string)($env['DB_NAME'] ?? getenv('DB_NAME') ?: 'project');
$DB_USER = (string)($env['DB_USER'] ?? getenv('DB_USER') ?: 'root');
$DB_PASS = (string)($env['DB_PASS'] ?? getenv('DB_PASS') ?: '');

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit("<h1>CodeForge database connection failed</h1><p>{$message}</p><p>Check <code>config/.env</code>. XAMPP normally uses port <code>3306</code>.</p>");
}
