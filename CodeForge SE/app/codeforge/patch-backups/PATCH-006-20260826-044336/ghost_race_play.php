<?php
require_once __DIR__.'/core/bootstrap.php';
require_login($pdo);
$id=(string)($_GET['id']??'');
redirect('ghost_play.php?id='.urlencode($id));
