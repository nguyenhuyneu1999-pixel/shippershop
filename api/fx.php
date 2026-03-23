<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '?';
$attempts = $d->fetchAll("SELECT * FROM login_attempts WHERE created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY created_at DESC LIMIT 10");
echo json_encode(['ip' => $ip, 'recent_attempts' => count($attempts ?: []), 'attempts' => $attempts]);
