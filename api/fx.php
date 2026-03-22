<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookmark_folders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, icon VARCHAR(10) DEFAULT '📁', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_bf_user (user_id))");
    echo json_encode(['success' => true]);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
