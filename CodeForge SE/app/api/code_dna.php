<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/CodeDnaService.php';

$user = require_api_user($pdo);
$id = (string) ($_GET['user'] ?? $user['id']);

try {
    json_response([
        'ok' => true,
        'data' => (new CodeDnaService($pdo))->calculate($id),
    ]);
} catch (RuntimeException $error) {
    json_response(['ok' => false, 'error' => $error->getMessage()], 404);
} catch (Throwable $error) {
    error_log('Code DNA API error: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => 'Could not calculate Code DNA.'], 500);
}
