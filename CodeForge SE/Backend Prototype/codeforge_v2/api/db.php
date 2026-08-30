<?php
header("Content-Type: application/json");

/**
 * Load .env for localhost ONLY
 * (Cloud platforms ignore this and use dashboard env vars)
 */
$envPath = __DIR__ . "/../config/.env";
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), "#")) continue;
        [$key, $value] = explode("=", $line, 2);
        putenv(trim($key) . "=" . trim($value));
    }
}

/**
 * Read environment variables
 */
$host = getenv("DB_HOST");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$port = getenv("DB_PORT") ?: 3306;

/**
 * Safety check
 */
if (!$host || !$db || !$user) {
    http_response_code(500);
    echo json_encode(["error" => "Database environment variables missing"]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
