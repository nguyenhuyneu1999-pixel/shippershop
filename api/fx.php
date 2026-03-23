<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];

$sqls = [
    // Feed: covering index for status + created_at + hot sort columns
    "ALTER TABLE posts ADD INDEX IF NOT EXISTS idx_feed_cover (`status`, created_at DESC, likes_count, comments_count, shares_count, user_id)",
    
    // Feed: hot score pre-calculated index  
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS hot_score INT DEFAULT 0",
    
    // Group posts: covering index
    "ALTER TABLE group_posts ADD INDEX IF NOT EXISTS idx_gp_cover (group_id, `status`, created_at DESC, likes_count, user_id)",
    
    // Conversations: composite index for user lookup
    "ALTER TABLE conversations ADD INDEX IF NOT EXISTS idx_conv_lookup (`status`, last_message_at DESC)",
    
    // Post likes: covering for vote check
    "ALTER TABLE post_likes ADD INDEX IF NOT EXISTS idx_pl_cover (post_id, user_id)",
    
    // Messages: conversation + created_at for pagination
    "ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_msg_conv (conversation_id, created_at DESC)",
    
    // Follows: both directions fast
    "ALTER TABLE follows ADD INDEX IF NOT EXISTS idx_follow_pair (follower_id, following_id)",
    
    // Users: status + online for listings
    "ALTER TABLE users ADD INDEX IF NOT EXISTS idx_user_active (`status`, is_online, last_active)",
    
    // Notifications read status
    "ALTER TABLE notification_reads ADD INDEX IF NOT EXISTS idx_nr_user (user_id, notif_key)",
];

foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 60); }
}

// Update hot_score for existing posts
try {
    $pdo->exec("UPDATE posts SET hot_score = likes_count * 3 + comments_count * 5 + shares_count * 2 WHERE `status` = 'active'");
    $r[] = 'hot_score updated';
} catch (Exception $e) { $r[] = 'hot_score: ' . substr($e->getMessage(), 0, 40); }

echo json_encode(['success' => true, 'results' => $r]);
