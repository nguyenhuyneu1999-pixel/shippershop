<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
$sqls = [
    "CREATE TABLE IF NOT EXISTS bookmark_folders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, icon VARCHAR(10) DEFAULT '📁', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_user (user_id))",
    "ALTER TABLE saved_posts ADD COLUMN IF NOT EXISTS folder_id INT DEFAULT NULL",
    "ALTER TABLE saved_posts ADD INDEX IF NOT EXISTS idx_folder (folder_id)",
    "ALTER TABLE marketplace_listings ADD COLUMN IF NOT EXISTS `condition` ENUM('new','used','like_new') DEFAULT 'used'",
];
foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 50); }
}
echo json_encode(['success' => true, 'results' => $r]);
