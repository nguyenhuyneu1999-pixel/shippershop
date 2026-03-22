<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];

// Test key queries with EXPLAIN
$queries = [
    'feed_main' => "EXPLAIN SELECT p.*, u.fullname FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' ORDER BY p.created_at DESC LIMIT 20",
    'feed_count' => "EXPLAIN SELECT COUNT(*) FROM posts WHERE `status`='active'",
    'comments' => "EXPLAIN SELECT c.*, u.fullname FROM comments c LEFT JOIN users u ON c.user_id=u.id WHERE c.post_id=1038 AND c.`status`='active' ORDER BY c.created_at ASC",
    'likes_batch' => "EXPLAIN SELECT post_id FROM likes WHERE user_id=2 AND post_id IN (1038,1039,1040)",
    'conversations' => "EXPLAIN SELECT id,user1_id,user2_id,last_message,last_message_at FROM conversations WHERE (user1_id=2 OR user2_id=2) AND `status`='active' ORDER BY last_message_at DESC",
    'unread_count' => "EXPLAIN SELECT conversation_id,COUNT(*) FROM messages WHERE conversation_id IN (1,2,3) AND sender_id!=2 AND is_read=0 GROUP BY conversation_id",
    'group_posts' => "EXPLAIN SELECT gp.*, u.fullname FROM group_posts gp JOIN users u ON gp.user_id=u.id WHERE gp.group_id=1 AND gp.`status`='active' ORDER BY gp.created_at DESC LIMIT 20",
    'notifications' => "EXPLAIN SELECT * FROM notifications WHERE user_id=2 AND is_read=0 ORDER BY created_at DESC LIMIT 20",
];

foreach ($queries as $name => $sql) {
    try {
        $stmt = $pdo->query($sql);
        $explain = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $row = $explain[0] ?? [];
        $results[$name] = [
            'type' => $row['type'] ?? '?',
            'rows' => intval($row['rows'] ?? 0),
            'key' => $row['key'] ?? 'NONE',
            'extra' => $row['Extra'] ?? '',
            'slow' => (intval($row['rows'] ?? 0) > 500) ? 'YES' : 'no',
        ];
    } catch (Exception $e) {
        $results[$name] = ['error' => $e->getMessage()];
    }
}

echo json_encode(['success' => true, 'explains' => $results], JSON_PRETTY_PRINT);
