<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain');

// Add missing email column
try {
    $pdo->exec("ALTER TABLE login_attempts ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `user_id`");
    echo "Added email column OK\n";
} catch (Throwable $e) {
    echo "email column: " . $e->getMessage() . "\n";
}

// Add missing indexes if needed
try {
    $pdo->exec("ALTER TABLE login_attempts ADD INDEX idx_ip_time (ip, created_at)");
    echo "Added idx_ip_time\n";
} catch (Throwable $e) {
    echo "idx_ip_time: exists\n";
}

// Test query that auth.php will use
try {
    $r = $pdo->prepare("SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > ?");
    $r->execute(['127.0.0.1', date('Y-m-d H:i:s', time() - 900)]);
    echo "Brute force query OK: " . $r->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";
} catch (Throwable $e) {
    echo "Query ERR: " . $e->getMessage() . "\n";
}

// Test insert 
try {
    $pdo->prepare("INSERT INTO login_attempts (ip, email, success, created_at) VALUES (?, ?, 0, NOW())")->execute(['127.0.0.1', 'test']);
    echo "Insert OK\n";
    $pdo->exec("DELETE FROM login_attempts WHERE ip='127.0.0.1'");
    echo "Cleanup OK\n";
} catch (Throwable $e) {
    echo "Insert ERR: " . $e->getMessage() . "\n";
}

echo "DONE\n";
