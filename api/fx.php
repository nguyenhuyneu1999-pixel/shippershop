<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
$sqls = [
    "CREATE TABLE IF NOT EXISTS post_polls (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, question VARCHAR(500), expires_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY (post_id))",
    "CREATE TABLE IF NOT EXISTS poll_options (id INT AUTO_INCREMENT PRIMARY KEY, poll_id INT NOT NULL, text VARCHAR(200) NOT NULL, vote_count INT DEFAULT 0, INDEX (poll_id))",
    "CREATE TABLE IF NOT EXISTS poll_votes (id INT AUTO_INCREMENT PRIMARY KEY, poll_id INT NOT NULL, option_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_vote (poll_id, user_id), INDEX (option_id))",
    "CREATE TABLE IF NOT EXISTS user_mutes (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, muted_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY (user_id, muted_id))",
];
foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 50); }
}
echo json_encode(['success' => true, 'results' => $r]);
