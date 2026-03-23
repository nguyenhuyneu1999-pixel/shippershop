<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$cols = $d->fetchAll("SHOW COLUMNS FROM map_pins");
$colNames = array_map(function($c){return $c['Field'];}, $cols);
echo json_encode(['columns' => $colNames, 'count' => count($cols)]);
