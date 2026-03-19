<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// Get the 20 real posts just inserted
$realPosts = $d->fetchAll("SELECT id, content, images, type, province FROM posts WHERE `status`='active' AND LENGTH(content) > 150 ORDER BY created_at DESC LIMIT 20");

echo "Found " . count($realPosts) . " real quality posts\n\n";

$count = 0;
$scheduleBase = strtotime('+1 hour');

foreach ($realPosts as $i => $post) {
    $content = $post['content'];
    $postUrl = "https://shippershop.vn/post-detail.html?id=" . $post['id'];
    
    // Facebook version - add CTA
    $fbContent = $content . "\n\n📱 Thảo luận thêm tại cộng đồng shipper: shippershop.vn\n\n#shipper #giaohang #congdongshipper #GHTK #GHN";
    
    // TikTok version - shorter + hook
    $lines = explode("\n", $content);
    $firstLine = trim($lines[0]);
    $tkContent = "🏍️ " . $firstLine . "\n\n" . implode("\n", array_slice($lines, 0, 6)) . "\n\n📱 Chi tiết: shippershop.vn\n#shipper #giaohang #tiktokshipper";
    
    // Schedule spread across next 5 days, 3-4 posts per day at optimal times
    $day = intval($i / 4);
    $slot = $i % 4;
    $hours = [8, 12, 18, 21][$slot];
    $schedTime = date('Y-m-d H:i:s', strtotime("+{$day} days {$hours}:00:00"));
    
    // Check duplicate
    $exists = $d->fetchOne("SELECT id FROM content_queue WHERE source_post_id = ? AND type = 'facebook'", [$post['id']]);
    if ($exists) continue;
    
    // Insert FB
    $d->query("INSERT INTO content_queue (type, title, content, source_post_id, `status`, scheduled_at) VALUES ('facebook', ?, ?, ?, 'pending', ?)", [
        'FB: ' . mb_substr($firstLine, 0, 60),
        $fbContent,
        $post['id'],
        $schedTime
    ]);
    
    // Insert TikTok
    $tkTime = date('Y-m-d H:i:s', strtotime($schedTime . ' +30 minutes'));
    $d->query("INSERT INTO content_queue (type, title, content, source_post_id, `status`, scheduled_at) VALUES ('tiktok', ?, ?, ?, 'pending', ?)", [
        'TK: ' . mb_substr($firstLine, 0, 60),
        $tkContent,
        $post['id'],
        $tkTime
    ]);
    
    $count += 2;
    echo "✅ Queued FB+TK for post #{$post['id']}: " . mb_substr($firstLine, 0, 40) . "...\n";
}

echo "\n🎉 Added $count items to content queue\n";
$pending = $d->fetchOne("SELECT COUNT(*) c FROM content_queue WHERE `status`='pending'")['c'];
echo "Total pending: $pending\n";
