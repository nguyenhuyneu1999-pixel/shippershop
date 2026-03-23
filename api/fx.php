<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS stories (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, content VARCHAR(200),
        image_url VARCHAR(255), bg_color VARCHAR(10) DEFAULT '#7C3AED',
        `status` ENUM('active','expired','deleted') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(created_at)
    )");
    $r[] = 'stories OK';
    $pdo->exec("CREATE TABLE IF NOT EXISTS story_views (
        id INT AUTO_INCREMENT PRIMARY KEY, story_id INT NOT NULL, user_id INT NOT NULL,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY(story_id, user_id), INDEX(story_id)
    )");
    $r[] = 'story_views OK';
} catch (Throwable $e) { $r[] = $e->getMessage(); }
echo json_encode(['success' => true, 'results' => $r]);
