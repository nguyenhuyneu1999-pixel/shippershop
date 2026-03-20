<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json');
$token = generateJWT(2, 'admin@shippershop.vn', 'admin');
echo json_encode(['token' => $token]);
