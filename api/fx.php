<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_pinned TINYINT DEFAULT 0 AFTER edited_at");
    echo json_encode(['success' => true]);
} catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
