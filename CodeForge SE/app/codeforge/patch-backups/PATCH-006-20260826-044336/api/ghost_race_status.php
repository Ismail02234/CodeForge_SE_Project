<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/GhostRaceService.php';
header('Content-Type: application/json; charset=utf-8');
$user=require_login($pdo);$id=(string)($_GET['id']??'');$service=new GhostRaceService($pdo);$race=$service->getRace($id,(string)$user['id']);if(!$race){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Race not found']);exit;}echo json_encode(['ok'=>true,'result'=>$race['result'],'virtual_elapsed'=>$service->virtualElapsed($race),'ghost_time'=>(int)$race['ghost_time'],'my_events'=>$service->challengerEvents((string)$race['challenger_session_id'])]);
