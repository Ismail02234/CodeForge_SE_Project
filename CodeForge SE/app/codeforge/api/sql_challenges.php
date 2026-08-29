<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/SqlBattleService.php';

require_api_user($pdo);
$rows = (new SqlBattleService($pdo))->challenges();

foreach ($rows as &$row) {
    unset($row['reference_query']);
}
unset($row);

json_response(['ok' => true, 'data' => $rows]);
