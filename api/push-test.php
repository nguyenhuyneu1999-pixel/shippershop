<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Simulate what notifications.php does for user_id=2 (Admin)
$userId=2;

echo "=== NOTIFICATION SOURCE TRACE ===\n";
echo "User: $userId (Admin ShipperShop)\n\n";

echo "--- Source 1: LIKES on my posts ---\n";
$likes=$d->fetchAll("
    SELECT 'like' as type, l.created_at, u.fullname as actor_name, u.id as actor_id,
           p.id as post_id, LEFT(p.content,40) as post_preview
    FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id
    WHERE p.user_id=? AND l.user_id!=? AND p.status='active'
    ORDER BY l.created_at DESC LIMIT 10
", [$userId, $userId]);
echo "Found: ".count($likes)." likes\n";
foreach($likes as $l){
    echo "  ".$l['actor_name']." đã thích bài \"".$l['post_preview']."...\" (".$l['created_at'].")\n";
}

echo "\n--- Source 2: COMMENTS on my posts ---\n";
$cmts=$d->fetchAll("
    SELECT 'comment' as type, c.created_at, u.fullname as actor_name, 
           p.id as post_id, LEFT(c.content,40) as cmt_preview
    FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id
    WHERE p.user_id=? AND c.user_id!=? AND p.status='active'
    ORDER BY c.created_at DESC LIMIT 10
", [$userId, $userId]);
echo "Found: ".count($cmts)." comments\n";
foreach($cmts as $c){
    echo "  ".$c['actor_name']." bình luận \"".$c['cmt_preview']."\" (".$c['created_at'].")\n";
}

echo "\n--- NOT included yet ---\n";
echo "❌ follows (ai theo dõi bạn)\n";
echo "❌ shares (ai chuyển tiếp bài)\n";
echo "❌ group_post_likes (ai thích bài trong nhóm)\n";
echo "❌ group_post_comments (ai bình luận trong nhóm)\n";
echo "❌ messages (tin nhắn mới)\n";
echo "❌ traffic_confirms (ai xác nhận cảnh báo)\n";

echo "\n--- Read status ---\n";
$reads=$d->fetchAll("SELECT COUNT(*) as c FROM notification_reads WHERE user_id=?",[$userId]);
echo "Read notifications: ".$reads[0]['c']."\n";

echo "\nDONE\n";
