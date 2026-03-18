<?php
set_time_limit(60);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$db=db();

$sqls = [
    // Subscription plans
    "CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        price DECIMAL(15,2) NOT NULL DEFAULT 0,
        duration_days INT NOT NULL DEFAULT 30,
        features TEXT,
        badge VARCHAR(50),
        badge_color VARCHAR(20),
        max_posts_per_day INT DEFAULT 5,
        priority_support TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // User subscriptions
    "CREATE TABLE IF NOT EXISTS user_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id INT NOT NULL,
        `status` ENUM('active','expired','cancelled','pending') DEFAULT 'pending',
        started_at DATETIME,
        expires_at DATETIME,
        auto_renew TINYINT(1) DEFAULT 1,
        transaction_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (`status`, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Security: API rate limiting
    "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        endpoint VARCHAR(100) NOT NULL,
        hits INT DEFAULT 1,
        window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_endpoint (ip, endpoint, window_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Security: Login attempts
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        user_id INT,
        success TINYINT(1) DEFAULT 0,
        user_agent VARCHAR(500),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Security: CSRF tokens
    "CREATE TABLE IF NOT EXISTS csrf_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Security: Audit log
    "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip VARCHAR(45),
        user_agent VARCHAR(500),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_action (user_id, action, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Payment methods saved
    "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('bank','momo','zalopay','vnpay') NOT NULL,
        account_number VARCHAR(100),
        account_name VARCHAR(200),
        bank_name VARCHAR(100),
        is_default TINYINT(1) DEFAULT 0,
        is_verified TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Add pin_code to wallets for transaction security
    "ALTER TABLE wallets ADD COLUMN pin_hash VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE wallets ADD COLUMN pin_attempts INT DEFAULT 0",
    "ALTER TABLE wallets ADD COLUMN locked_until DATETIME DEFAULT NULL",
    "ALTER TABLE wallets ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",

    // Add 2FA fields to users
    "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN last_password_change DATETIME DEFAULT NULL",
];

foreach ($sqls as $sql) {
    try {
        $db->query($sql, []);
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } catch (Throwable $e) {
        echo "SKIP: " . substr($e->getMessage(), 0, 80) . "\n";
    }
}

// Insert default subscription plans
$plans = [
    ['Miễn phí', 'free', 0, 99999, '["Đăng 3 bài/ngày","Xem feed","Nhắn tin","Ghi chú bài viết"]', NULL, NULL, 3, 0, 0],
    ['Shipper Pro', 'pro', 49000, 30, '["Đăng 20 bài/ngày","Badge Pro","Ưu tiên hiện bài","Lọc đơn nâng cao","Xem ai thích bài","Thống kê thu nhập"]', '⭐ PRO', '#ff9800', 20, 0, 1],
    ['Shipper VIP', 'vip', 99000, 30, '["Đăng không giới hạn","Badge VIP","Ưu tiên #1 trên feed","Hỗ trợ ưu tiên 24/7","Tất cả tính năng Pro","Quảng cáo bài viết","Phân tích chi tiết"]', '👑 VIP', '#e91e63', 999, 1, 2],
    ['Shipper Premium', 'premium', 199000, 30, '["Tất cả tính năng VIP","Badge Premium","Verify tick xanh","Quảng cáo ưu tiên","API access","Hỗ trợ riêng"]', '💎 PREMIUM', '#9c27b0', 999, 1, 3],
];

foreach ($plans as $p) {
    try {
        $existing = $db->fetchOne("SELECT id FROM subscription_plans WHERE slug=?", [$p[1]]);
        if (!$existing) {
            $db->query("INSERT INTO subscription_plans (name,slug,price,duration_days,features,badge,badge_color,max_posts_per_day,priority_support,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)", $p);
            echo "Plan: {$p[0]} ({$p[2]}đ)\n";
        } else {
            echo "Plan exists: {$p[0]}\n";
        }
    } catch (Throwable $e) {
        echo "Plan skip: " . $e->getMessage() . "\n";
    }
}

echo "\nDONE!\n";
