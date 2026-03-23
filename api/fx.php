<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();

$cols = $pdo->query("SHOW COLUMNS FROM map_pins")->fetchAll(PDO::FETCH_ASSOC);
$indexes = $pdo->query("SHOW INDEX FROM map_pins")->fetchAll(PDO::FETCH_ASSOC);
$count = $pdo->query("SELECT COUNT(*) as c FROM map_pins")->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'columns' => array_map(function($c){return $c['Field'].':'.$c['Type'];}, $cols),
    'indexes' => array_map(function($i){return $i['Key_name'].':'.$i['Column_name'];}, $indexes),
    'row_count' => $count['c']
]);
