<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS edited_at DATETIME DEFAULT NULL AFTER created_at");
    echo json_encode(['success' => true, 'message' => 'edited_at column added']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
