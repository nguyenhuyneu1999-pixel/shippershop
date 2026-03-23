<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];

$pdo->exec("CREATE TABLE IF NOT EXISTS daily_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_date DATE NOT NULL,
    deliveries INT DEFAULT 0,
    reward_tier VARCHAR(20) DEFAULT NULL,
    reward_claimed TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(user_id, reward_date),
    INDEX(reward_date)
)");
$r[] = 'daily_rewards OK';

// Add deliveries_today column to user_streaks for quick access
try {
    $pdo->exec("ALTER TABLE user_streaks ADD COLUMN deliveries_today INT DEFAULT 0");
    $r[] = 'deliveries_today column added';
} catch (Throwable $e) {
    $r[] = 'deliveries_today: ' . $e->getMessage();
}

echo json_encode(['success' => true, 'results' => $r]);
