<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// 1. Referral codes table
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS referral_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    uses_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_user (user_id)
)");
echo "✅ referral_codes\n";

// 2. Referral tracking
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS referral_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL,
    reward_referrer TINYINT DEFAULT 0,
    reward_referred TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_id)
)");
echo "✅ referral_logs\n";

// 3. XP & Gamification
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS user_xp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    xp INT NOT NULL,
    detail VARCHAR(200),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, created_at)
)");
echo "✅ user_xp\n";

// 4. Daily streaks
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS user_streaks (
    user_id INT PRIMARY KEY,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_active_date DATE,
    total_xp INT DEFAULT 0,
    level INT DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
echo "✅ user_streaks\n";

// 5. Auto content queue
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS content_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('tiktok','facebook','zalo','blog','push') NOT NULL,
    title VARCHAR(500),
    content TEXT,
    media_url VARCHAR(500),
    source_post_id INT,
    status ENUM('pending','published','failed') DEFAULT 'pending',
    scheduled_at DATETIME,
    published_at DATETIME,
    platform_post_id VARCHAR(200),
    error_log TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_schedule (status, scheduled_at),
    INDEX idx_type (type)
)");
echo "✅ content_queue\n";

// 6. Social accounts config
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    account_name VARCHAR(200),
    access_token TEXT,
    refresh_token TEXT,
    page_id VARCHAR(100),
    token_expires DATETIME,
    is_active TINYINT DEFAULT 1,
    config JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
echo "✅ social_accounts\n";

// 7. Marketing analytics
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS marketing_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    channel VARCHAR(50) NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    signups INT DEFAULT 0,
    cost DECIMAL(15,2) DEFAULT 0,
    revenue DECIMAL(15,2) DEFAULT 0,
    data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_date_channel (date, channel)
)");
echo "✅ marketing_analytics\n";

// Add ref_code column to users if not exists
try {
    $d->getConnection()->exec("ALTER TABLE users ADD COLUMN ref_code VARCHAR(20) UNIQUE AFTER role");
    echo "✅ users.ref_code added\n";
} catch(Throwable $e) {
    echo "⚠️ users.ref_code: already exists\n";
}
try {
    $d->getConnection()->exec("ALTER TABLE users ADD COLUMN referred_by INT AFTER ref_code");
    echo "✅ users.referred_by added\n";
} catch(Throwable $e) {
    echo "⚠️ users.referred_by: already exists\n";
}

echo "\n✅ ALL TABLES CREATED\n";
