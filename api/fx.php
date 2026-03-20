<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$cols = db()->fetchAll("SHOW COLUMNS FROM rate_limits");
echo json_encode(['columns' => array_map(function($c){return $c['Field'].':'.$c['Type'];}, $cols)]);
