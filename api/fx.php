<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$results = [];

$indexes = [
    // posts
    ["posts", "idx_user_status", "ALTER TABLE posts ADD INDEX idx_user_status (user_id, `status`, created_at)"],
    ["posts", "idx_province", "ALTER TABLE posts ADD INDEX idx_province (province, district)"],
    ["posts", "idx_sort", "ALTER TABLE posts ADD INDEX idx_sort (likes_count DESC, comments_count DESC, created_at DESC)"],
    // comments
    ["comments", "idx_post_status", "ALTER TABLE comments ADD INDEX idx_post_status (post_id, `status`, created_at)"],
    // likes
    ["likes", "idx_post_user", "ALTER TABLE likes ADD INDEX idx_post_user (post_id, user_id)"],
    ["likes", "idx_user", "ALTER TABLE likes ADD INDEX idx_user_created (user_id, created_at)"],
    // follows
    ["follows", "idx_pair", "ALTER TABLE follows ADD INDEX idx_pair (follower_id, following_id)"],
    ["follows", "idx_rev", "ALTER TABLE follows ADD INDEX idx_rev (following_id, follower_id)"],
    // messages
    ["messages", "idx_conv", "ALTER TABLE messages ADD INDEX idx_conv_created (conversation_id, created_at)"],
    ["messages", "idx_sender", "ALTER TABLE messages ADD INDEX idx_sender_created (sender_id, created_at)"],
    // conversations
    ["conversations", "idx_u1", "ALTER TABLE conversations ADD INDEX idx_u1_status (user1_id, `status`, last_message_at DESC)"],
    ["conversations", "idx_u2", "ALTER TABLE conversations ADD INDEX idx_u2_status (user2_id, `status`, last_message_at DESC)"],
    // group_posts
    ["group_posts", "idx_group", "ALTER TABLE group_posts ADD INDEX idx_group_status (group_id, `status`, created_at DESC)"],
    ["group_posts", "idx_gp_user", "ALTER TABLE group_posts ADD INDEX idx_gp_user_status (user_id, `status`)"],
    // group_post_likes
    ["group_post_likes", "idx_gpl", "ALTER TABLE group_post_likes ADD INDEX idx_gpl_pair (post_id, user_id)"],
    // group_members
    ["group_members", "idx_gm", "ALTER TABLE group_members ADD INDEX idx_gm_pair (group_id, user_id)"],
    // notifications
    ["notifications", "idx_notif", "ALTER TABLE notifications ADD INDEX idx_notif_user (user_id, created_at DESC)"],
    // users
    ["users", "idx_company", "ALTER TABLE users ADD INDEX idx_company (shipping_company)"],
    ["users", "idx_online", "ALTER TABLE users ADD INDEX idx_online (`status`, is_online, last_active DESC)"],
    // wallet_transactions
    ["wallet_transactions", "idx_wallet", "ALTER TABLE wallet_transactions ADD INDEX idx_wallet_user (user_id, created_at DESC)"],
    // post_likes
    ["post_likes", "idx_pl_pair", "ALTER TABLE post_likes ADD INDEX idx_pl_pair (post_id, user_id)"],
];

foreach ($indexes as $idx) {
    $table = $idx[0];
    $name = $idx[1];
    $sql = $idx[2];
    try {
        // Check if index already exists
        $existing = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name LIKE '%".str_replace("idx_", "", $name)."%'")->fetchAll();
        if (count($existing) > 2) {
            $results[] = ['table' => $table, 'index' => $name, 'status' => 'SKIP (exists)'];
            continue;
        }
        $pdo->exec($sql);
        $results[] = ['table' => $table, 'index' => $name, 'status' => 'OK'];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate key name') !== false) {
            $results[] = ['table' => $table, 'index' => $name, 'status' => 'SKIP (duplicate)'];
        } else {
            $results[] = ['table' => $table, 'index' => $name, 'status' => 'ERROR: '.$msg];
        }
    }
}

echo json_encode(['step' => 'indexes', 'results' => $results, 'total' => count($results)], JSON_PRETTY_PRINT);
