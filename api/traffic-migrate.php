<?php
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain');
$db = db();

try {
    $db->query("CREATE TABLE IF NOT EXISTS traffic_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category ENUM('traffic','weather','terrain','warning','other') NOT NULL DEFAULT 'traffic',
        content TEXT,
        images TEXT,
        video_url VARCHAR(500),
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        address VARCHAR(500),
        province VARCHAR(100),
        district VARCHAR(100),
        severity ENUM('low','medium','high','critical') DEFAULT 'medium',
        expires_at DATETIME NOT NULL,
        is_quick TINYINT(1) DEFAULT 0,
        confirms INT DEFAULT 0,
        denies INT DEFAULT 0,
        `status` ENUM('active','expired','removed') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status_expires (`status`, expires_at),
        INDEX idx_location (latitude, longitude),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
    echo "Created: traffic_alerts\n";
} catch (Throwable $e) { echo "Skip: " . $e->getMessage() . "\n"; }

try {
    $db->query("CREATE TABLE IF NOT EXISTS traffic_confirms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_id INT NOT NULL,
        user_id INT NOT NULL,
        vote ENUM('confirm','deny') NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_alert_user (alert_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
    echo "Created: traffic_confirms\n";
} catch (Throwable $e) { echo "Skip: " . $e->getMessage() . "\n"; }

try {
    // Add trust_score to users
    $db->query("ALTER TABLE users ADD COLUMN trust_score INT DEFAULT 0", []);
    echo "Added: trust_score\n";
} catch (Throwable $e) { echo "Skip: " . $e->getMessage() . "\n"; }

echo "DONE";
