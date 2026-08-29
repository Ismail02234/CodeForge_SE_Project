<?php

declare(strict_types=1);

function current_user(PDO $pdo): ?array
{
    static $cachedUser = null;
    static $cachedId = null;

    $userId = $_SESSION['user_id'] ?? null;
    if (!is_string($userId) || $userId === '') {
        return null;
    }

    if ($cachedId === $userId && is_array($cachedUser)) {
        return $cachedUser;
    }

    $stmt = $pdo->prepare(
        'SELECT id, username, role, rating, university, `rank`, created_at
         FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    $cachedId = $userId;
    $cachedUser = $user;
    return $user;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
    return $user;
}

function require_api_user(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        json_response(['ok' => false, 'error' => 'Authentication required.'], 401);
    }
    return $user;
}

function require_admin(PDO $pdo): array
{
    $user = require_login($pdo);
    if (($user['role'] ?? '') !== 'admin') {
        flash('error', 'Administrator privileges are required for that page.');
        redirect('dashboard.php');
    }
    return $user;
}

function login_user(array $user): void
{
    if (!isset($user['id'])) {
        throw new InvalidArgumentException('Cannot sign in without a user id.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (string) $user['id'];
    rotate_csrf_token();
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
