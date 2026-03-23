<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS msg_reactions (id INT AUTO_INCREMENT PRIMARY KEY, message_id INT NOT NULL, user_id INT NOT NULL, emoji VARCHAR(10) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY (message_id, user_id), INDEX (message_id))");
    echo json_encode(['success' => true]);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
