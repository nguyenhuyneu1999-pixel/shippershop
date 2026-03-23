<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$cols = $pdo->query("SHOW COLUMNS FROM user_badges")->fetchAll(PDO::FETCH_COLUMN);
echo json_encode(['columns' => $cols]);
