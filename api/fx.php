<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];

$sqls = [
    "CREATE TABLE IF NOT EXISTS user_blocks (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, blocked_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_block (user_id, blocked_id), INDEX idx_blocked (blocked_id))",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS scheduled_at DATETIME DEFAULT NULL",
    "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS badge VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS badge_color VARCHAR(10) DEFAULT NULL",
];

foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 60); }
}

// Set badge values
try {
    $pdo->exec("UPDATE subscription_plans SET badge='⭐ PRO', badge_color='#ff9800' WHERE id=2");
    $pdo->exec("UPDATE subscription_plans SET badge='👑 VIP', badge_color='#9c27b0' WHERE id=3");
    $pdo->exec("UPDATE subscription_plans SET badge='💎 PREMIUM', badge_color='#e91e63' WHERE id=4");
    $r[] = 'badges set';
} catch (Exception $e) { $r[] = 'badge: ' . $e->getMessage(); }

echo json_encode(['success' => true, 'results' => $r, 'tables' => intval($pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchColumn())]);
