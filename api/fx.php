<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();
$cols = $d->fetchAll("SHOW COLUMNS FROM conversations");
$colNames = array_column($cols, 'Field');
echo json_encode(['columns'=>$colNames], JSON_PRETTY_PRINT);
