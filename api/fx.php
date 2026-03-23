<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) DEFAULT NULL AFTER avatar");
    echo json_encode(['success' => true]);
} catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
