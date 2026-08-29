<?php
require_once __DIR__.'/core/bootstrap.php';
require_login($pdo);
redirect('university.php');
