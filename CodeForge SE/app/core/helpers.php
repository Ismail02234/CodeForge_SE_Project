<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    if ($location === '' || preg_match('/[\r\n]/', $location)) {
        throw new InvalidArgumentException('Unsafe redirect target.');
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $location) || str_starts_with($location, '//')) {
        throw new InvalidArgumentException('External redirects are not allowed.');
    }

    header('Location: ' . $location, true, 302);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');

    try {
        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        http_response_code(500);
        echo '{"ok":false,"error":"Response encoding failed."}';
    }

    exit;
}

function request_method(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function rotate_csrf_token(): string
{
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Security token expired or invalid. Please reload the page and try again.');
    }
}

function int_between(mixed $value, int $min, int $max, int $default = 0): int
{
    if (!is_numeric($value)) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

function format_duration(?int $seconds): string
{
    if ($seconds === null) {
        return '—';
    }

    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remaining = $seconds % 60;

    return $hours > 0
        ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining)
        : sprintf('%02d:%02d', $minutes, $remaining);
}

function verdict_class(string $verdict): string
{
    return match (strtoupper($verdict)) {
        'AC' => 'status-success',
        'WA' => 'status-danger',
        'TLE', 'MLE' => 'status-warning',
        'CE', 'RE' => 'status-muted',
        default => 'status-info',
    };
}

function difficulty_points(string $difficulty): int
{
    return match (strtolower($difficulty)) {
        'easy' => 1,
        'medium' => 2,
        'hard' => 3,
        default => 1,
    };
}

function difficulty_class(string $difficulty): string
{
    return match (strtolower($difficulty)) {
        'easy' => 'pill-easy',
        'medium' => 'pill-medium',
        'hard' => 'pill-hard',
        default => 'pill-neutral',
    };
}

function generate_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(6));
}

function active_page(string $name): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $name ? 'is-active' : '';
}

function current_path(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '');
}

function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : '1';
    return $path . '?v=' . rawurlencode($version);
}

function user_safe_error(Throwable $error, string $fallback = 'Something went wrong. Please try again.'): string
{
    if ($error instanceof RuntimeException) {
        return $error->getMessage();
    }

    error_log('CodeForge action error: ' . $error->getMessage());
    return $fallback;
}
