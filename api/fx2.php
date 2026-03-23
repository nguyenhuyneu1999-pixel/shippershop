<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$d = db();

$streak = $d->fetchOne("SELECT * FROM user_streaks WHERE user_id = 2");
echo json_encode(['streak_data' => $streak, 'today' => date('Y-m-d')]);
