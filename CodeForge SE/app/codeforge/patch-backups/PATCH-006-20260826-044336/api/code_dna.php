<?php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../services/CodeDnaService.php';
header('Content-Type: application/json; charset=utf-8');
$user=require_login($pdo);$id=(string)($_GET['user']??$user['id']);
try{echo json_encode(['ok'=>true,'data'=>(new CodeDnaService($pdo))->calculate($id)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(404);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
