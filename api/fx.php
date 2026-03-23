<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("ALTER TABLE conversation_members ADD COLUMN IF NOT EXISTS is_archived TINYINT DEFAULT 0");
    echo json_encode(['success' => true]);
} catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
