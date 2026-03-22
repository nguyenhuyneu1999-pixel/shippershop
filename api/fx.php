<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0");
    echo json_encode(['success' => true, 'message' => 'views_count added']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
