<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$cols=$pdo->query("DESCRIBE audit_log")->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($cols);
