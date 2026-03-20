<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json');

// Simulate vote
try {
    $userId = getAuthUserId();
    echo json_encode(['step' => 'auth', 'userId' => $userId]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
