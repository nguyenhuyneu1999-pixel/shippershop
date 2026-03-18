<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
$results = [];

// ============================================
// 1. ADD MISSING INDEXES
// ============================================
$indexes = [
    // Feed performance (CRITICAL - currently FULL SCAN)
    ["posts", "idx_feed", "(status, created_at DESC)"],
    ["posts", "idx_location", "(province, district, status, created_at DESC)"],
    ["posts", "idx_user_posts", "(user_id, status, created_at DESC)"],
    ["posts", "idx_type_feed", "(post_type, status, created_at DESC)"],
    
    // Like/Save batch check (fix N+1)
    ["likes", "idx_user_post", "(post_id, user_id)", true],
    ["likes", "idx_user_likes", "(user_id, post_id)"],
    ["saved_posts", "idx_user_post", "(user_id, post_id)", true],
    
    // Comments
    ["comments", "idx_post_cmt", "(post_id, status, created_at)"],
    ["comments", "idx_user_cmt", "(user_id, status)"],
    
    // Notifications
    ["notification_reads", "idx_user_key", "(user_id, notif_key)", true],
    
    // Messages
    ["messages", "idx_conv_time", "(conversation_id, created_at DESC)"],
    ["messages", "idx_unread", "(conversation_id, sender_id, is_read)"],
    
    // Conversations
    ["conversations", "idx_last_msg", "(last_message_at DESC)"],
    
    // Rate limits cleanup
    ["rate_limits", "idx_cleanup", "(window_start)"],
    
    // Audit log
    ["audit_log", "idx_user_time", "(user_id, created_at DESC)"],
    
    // Wallet transactions
    ["wallet_transactions", "idx_user_time", "(user_id, created_at DESC)"],
    
    // CSRF tokens cleanup
    ["csrf_tokens", "idx_cleanup", "(expires_at)"],
    
    // Login attempts (for brute force protection)
    ["login_attempts", "idx_ip_time", "(ip, created_at)"],
    ["login_attempts", "idx_user_time", "(user_id, created_at)"],
];

foreach ($indexes as $idx) {
    $table = $idx[0];
    $name = $idx[1];
    $cols = $idx[2];
    $unique = $idx[3] ?? false;
    $type = $unique ? "UNIQUE INDEX" : "INDEX";
    
    try {
        // Check if index exists
        $existing = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$name'")->fetchAll();
        if (!empty($existing)) {
            $results[] = "SKIP $table.$name (exists)";
            continue;
        }
        $pdo->exec("ALTER TABLE `$table` ADD $type `$name` $cols");
        $results[] = "OK   $table.$name $cols";
    } catch (Throwable $e) {
        $results[] = "ERR  $table.$name: " . $e->getMessage();
    }
}

// ============================================
// 2. DENORMALIZE COUNTS INTO POSTS
// ============================================
try {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    if (!in_array('likes_count', $cols)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN `likes_count` INT NOT NULL DEFAULT 0");
        $results[] = "OK   posts.likes_count column added";
    } else {
        $results[] = "SKIP posts.likes_count (exists)";
    }
    
    if (!in_array('comments_count', $cols)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN `comments_count` INT NOT NULL DEFAULT 0");
        $results[] = "OK   posts.comments_count column added";
    } else {
        $results[] = "SKIP posts.comments_count (exists)";
    }
    
    if (!in_array('shares_count', $cols)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN `shares_count` INT NOT NULL DEFAULT 0");
        $results[] = "OK   posts.shares_count column added";
    } else {
        $results[] = "SKIP posts.shares_count (exists)";
    }
    
    // Populate counts from existing data
    $pdo->exec("UPDATE posts p SET 
        likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id),
        comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active')
    ");
    $results[] = "OK   Synced likes_count + comments_count for all posts";
    
} catch (Throwable $e) {
    $results[] = "ERR  denormalize: " . $e->getMessage();
}

// ============================================
// 3. ADD login_attempts TABLE IF NOT EXISTS
// ============================================
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
    $results[] = "OK   login_attempts table ready";
} catch (Throwable $e) {
    $results[] = "ERR  login_attempts: " . $e->getMessage();
}

// ============================================
// 4. VERIFY WITH EXPLAIN
// ============================================
try {
    $exp = $pdo->query("EXPLAIN SELECT p.*, u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $results[] = "\nEXPLAIN feed: type={$exp[0]['type']} key={$exp[0]['key']} rows={$exp[0]['rows']}";
    
    $exp2 = $pdo->query("EXPLAIN SELECT p.* FROM posts p WHERE p.province='Ha Noi' AND p.status='active' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $results[] = "EXPLAIN location: type={$exp2[0]['type']} key={$exp2[0]['key']} rows={$exp2[0]['rows']}";
} catch (Throwable $e) {
    $results[] = "EXPLAIN err: " . $e->getMessage();
}

echo implode("\n", $results);
