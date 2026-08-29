<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/GhostRaceService.php';

$user = require_api_user($pdo);
$id = trim((string) ($_GET['id'] ?? ''));

if ($id === '') {
    json_response(['ok' => false, 'error' => 'Race id is required.'], 422);
}

$service = new GhostRaceService($pdo);
$race = $service->getRace($id, (string) $user['id']);

if (!$race) {
    json_response(['ok' => false, 'error' => 'Race not found.'], 404);
}

json_response([
    'ok' => true,
    'result' => $race['result'],
    'virtual_elapsed' => $service->virtualElapsed($race),
    'ghost_time' => (int) $race['ghost_time'],
    'my_events' => $service->challengerEvents((string) $race['challenger_session_id']),
]);
