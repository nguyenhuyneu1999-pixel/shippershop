<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();

// What notification API returns for user#3 (the logged-in test user)
echo "=== Notification query for user#3 ===\n";
$likes = $pdo->query("SELECT 'like' as type, l.created_at, u.fullname as actor_name, 
    p.id as post_id, p.status as post_status, CONCAT('like_',l.user_id,'_',l.post_id) as notif_key
    FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id
    WHERE p.user_id=3 AND l.user_id!=3
    ORDER BY l.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

foreach ($likes as $l) {
    echo "  post_id={$l['post_id']} status={$l['post_status']} actor={$l['actor_name']} key={$l['notif_key']}\n";
}

echo "\n=== Check these post_ids exist ===\n";
$pids = array_unique(array_column($likes, 'post_id'));
foreach ($pids as $pid) {
    $p = $pdo->query("SELECT id, status, user_id, LEFT(content,50) as preview FROM posts WHERE id=$pid")->fetch(PDO::FETCH_ASSOC);
    if ($p) echo "  post#$pid: status={$p['status']} owner={$p['user_id']} \"{$p['preview']}\"\n";
    else echo "  post#$pid: NOT IN DB!\n";
}

echo "\n=== What user#3 sees (posts they own) ===\n";
$posts = $pdo->query("SELECT id, status, LEFT(content,50) as preview FROM posts WHERE user_id=3 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($posts as $p) echo "  post#{$p['id']}: status={$p['status']} \"{$p['preview']}\"\n";
if (empty($posts)) echo "  (no posts by user#3)\n";

echo "\n=== Check likes table referencing non-existent posts ===\n";
$orphans = $pdo->query("SELECT l.post_id, COUNT(*) as cnt FROM likes l LEFT JOIN posts p ON l.post_id=p.id WHERE p.id IS NULL GROUP BY l.post_id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "Orphan likes (post deleted): " . count($orphans) . "\n";
foreach ($orphans as $o) echo "  post_id={$o['post_id']}: {$o['cnt']} likes\n";

$inactive = $pdo->query("SELECT l.post_id, p.status, COUNT(*) as cnt FROM likes l JOIN posts p ON l.post_id=p.id WHERE p.status!='active' GROUP BY l.post_id, p.status LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "Likes on non-active posts: " . count($inactive) . "\n";
foreach ($inactive as $o) echo "  post_id={$o['post_id']} status={$o['status']}: {$o['cnt']} likes\n";

echo "\n=== Posts range ===\n";
$r = $pdo->query("SELECT MIN(id) as mn, MAX(id) as mx, COUNT(*) as cnt FROM posts WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
echo "Active posts: {$r['cnt']}, id range: {$r['mn']}-{$r['mx']}\n";

$r2 = $pdo->query("SELECT MIN(post_id) as mn, MAX(post_id) as mx FROM likes")->fetch(PDO::FETCH_ASSOC);
echo "Likes post_id range: {$r2['mn']}-{$r2['mx']}\n";
