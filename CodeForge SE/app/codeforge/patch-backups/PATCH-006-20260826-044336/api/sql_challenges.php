<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/SqlBattleService.php';
header('Content-Type: application/json; charset=utf-8');
require_login($pdo);$rows=(new SqlBattleService($pdo))->challenges();foreach($rows as &$row){unset($row['reference_query']);}unset($row);echo json_encode(['ok'=>true,'data'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
