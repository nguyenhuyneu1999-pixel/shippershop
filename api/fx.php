<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$cols = $d->fetchAll("SHOW COLUMNS FROM `groups`");
$colNames = array_map(function($c){return $c['Field'];}, $cols);

$ucols = $d->fetchAll("SHOW COLUMNS FROM users");
$uNames = array_map(function($c){return $c['Field'];}, $ucols);

echo json_encode(['groups_cols' => $colNames, 'users_cols' => $uNames]);
