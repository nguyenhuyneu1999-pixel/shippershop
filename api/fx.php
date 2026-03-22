<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];

// Compound indexes for feed queries
$indexes = [
    // Feed: WHERE status='active' ORDER BY created_at DESC (most common)
    "ALTER TABLE posts ADD INDEX IF NOT EXISTS idx_feed_main (`status`, created_at DESC)",
    // Feed with type filter
    "ALTER TABLE posts ADD INDEX IF NOT EXISTS idx_feed_type (`status`, type, created_at DESC)",
    // Comments: WHERE post_id=? AND status='active' ORDER BY created_at
    "ALTER TABLE comments ADD INDEX IF NOT EXISTS idx_cmt_post (`post_id`, `status`, created_at)",
    // Likes batch check
    "ALTER TABLE likes ADD INDEX IF NOT EXISTS idx_likes_user_posts (user_id, post_id)",
    // Saved posts batch check
    "ALTER TABLE saved_posts ADD INDEX IF NOT EXISTS idx_saved_user_posts (user_id, post_id)",
    // Messages: unread count per conversation
    "ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_msg_conv_read (conversation_id, sender_id, is_read)",
    // Conversations: user lookup
    "ALTER TABLE conversations ADD INDEX IF NOT EXISTS idx_conv_user1 (user1_id, `status`)",
    "ALTER TABLE conversations ADD INDEX IF NOT EXISTS idx_conv_user2 (user2_id, `status`)",
    // Group posts: group feed
    "ALTER TABLE group_posts ADD INDEX IF NOT EXISTS idx_gp_feed (group_id, `status`, created_at DESC)",
    // Follows: follower lookup
    "ALTER TABLE follows ADD INDEX IF NOT EXISTS idx_follow_pair (follower_id, following_id)",
    // Notifications: user unread
    "ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_notif_user (user_id, is_read, created_at DESC)",
    // Wallet transactions: user history
    "ALTER TABLE wallet_transactions ADD INDEX IF NOT EXISTS idx_wtx_user (user_id, created_at DESC)",
    // User subscriptions: active check
    "ALTER TABLE user_subscriptions ADD INDEX IF NOT EXISTS idx_usub_active (user_id, `status`, expires_at)",
];

foreach ($indexes as $sql) {
    try {
        $pdo->exec($sql);
        $name = preg_match('/idx_\w+/', $sql, $m) ? $m[0] : '?';
        $results[] = "$name: OK";
    } catch (Exception $e) {
        $name = preg_match('/idx_\w+/', $sql, $m) ? $m[0] : '?';
        $results[] = "$name: " . $e->getMessage();
    }
}

echo json_encode(['success' => true, 'indexes_added' => count($results), 'results' => $results], JSON_PRETTY_PRINT);
