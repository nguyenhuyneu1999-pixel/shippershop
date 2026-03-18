<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();

// Check login_attempts structure
try {
    $cols = $pdo->query("SHOW COLUMNS FROM login_attempts")->fetchAll(PDO::FETCH_ASSOC);
    echo "login_attempts columns:\n";
    foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) {$c['Null']} {$c['Default']}\n";
} catch (Throwable $e) {
    echo "login_attempts table missing: " . $e->getMessage() . "\n";
    echo "Creating...\n";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ip` VARCHAR(45) NOT NULL,
            `user_id` INT DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `success` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ip_time` (`ip`, `created_at`),
            INDEX `idx_user_time` (`user_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "Created OK\n";
    } catch (Throwable $e2) {
        echo "Create ERR: " . $e2->getMessage() . "\n";
    }
}

// Test insert
try {
    $pdo->prepare("INSERT INTO login_attempts (ip, email, success, created_at) VALUES (?, ?, 0, NOW())")->execute(['127.0.0.1', 'test']);
    echo "Insert OK\n";
    $pdo->exec("DELETE FROM login_attempts WHERE ip='127.0.0.1' AND email='test'");
} catch (Throwable $e) {
    echo "Insert ERR: " . $e->getMessage() . "\n";
}
