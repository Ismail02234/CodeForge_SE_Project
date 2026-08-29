<?php

declare(strict_types=1);

$envFile = __DIR__ . '/.env';
$env = file_exists($envFile) ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: []) : [];

$DB_HOST = (string) ($env['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
$DB_NAME = (string) ($env['DB_NAME'] ?? getenv('DB_NAME') ?: 'project');
$DB_USER = (string) ($env['DB_USER'] ?? getenv('DB_USER') ?: 'root');
$DB_PASS = (string) ($env['DB_PASS'] ?? getenv('DB_PASS') ?: '');
$explicitPort = trim((string) ($env['DB_PORT'] ?? getenv('DB_PORT') ?: ''));

// CodeForge's original XAMPP setup used 3307; standard XAMPP usually uses 3306.
// An explicit DB_PORT always wins and avoids probing overhead.
$ports = $explicitPort !== '' ? [$explicitPort] : ['3307', '3306'];
$pdo = null;
$lastError = null;
$DB_PORT = $ports[0];

foreach ($ports as $port) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 3,
        ];

        if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[constant('PDO::MYSQL_ATTR_MULTI_STATEMENTS')] = false;
        }

        $candidate = new PDO(
            "mysql:host={$DB_HOST};port={$port};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            $options
        );

        $candidate->exec("SET time_zone = '+00:00'");
        $candidate->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

        $pdo = $candidate;
        $DB_PORT = $port;
        break;
    } catch (PDOException $error) {
        $lastError = $error;
    }
}

if (!$pdo instanceof PDO) {
    http_response_code(500);
    error_log('CodeForge database connection failed: ' . ($lastError?->getMessage() ?? 'Unknown database error'));

    $safePorts = e(implode(', ', $ports));
    exit(
        '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>CodeForge DB Error</title><style>body{font:16px system-ui;background:#060709;color:#eceff4;padding:40px;max-width:820px;margin:auto}' .
        'code{color:#ff6949}div{padding:24px;border:1px solid #30201d;border-radius:12px;background:#0c0d10}</style></head><body>' .
        '<div><h1>CodeForge database connection failed</h1><p>CodeForge could not reach MariaDB.</p>' .
        '<p>Checked port(s): <code>' . $safePorts . '</code>. Confirm XAMPP MySQL is running and the database is named <code>project</code>.</p>' .
        '<p>If needed, set <code>DB_PORT=3307</code> in <code>config/.env</code>.</p></div></body></html>'
    );
}
