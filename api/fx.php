<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: application/json');

// Generate JWT for user 2 (Admin) for testing
$token = generateJWT(['user_id' => 2]);
echo json_encode(['token' => $token, 'user_id' => 2]);
