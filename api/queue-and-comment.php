<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// 1. Queue new real posts for Facebook
$newPosts = $d->fetchAll("SELECT id, content FROM posts WHERE `status`='active' AND LENGTH(content) > 200 AND id NOT IN (SELECT COALESCE(source_post_id,0) FROM content_queue WHERE source_post_id IS NOT NULL) ORDER BY created_at DESC LIMIT 15");

echo "=== Queue for Facebook ===\n";
$qCount = 0;
foreach ($newPosts as $i => $p) {
    $lines = explode("\n", $p['content']);
    $title = mb_substr(trim($lines[0]), 0, 80);
    $fbContent = $p['content'] . "\n\n📱 Cộng đồng shipper: shippershop.vn\n\n#shipper #giaohang #congdongshipper #GHTK #GHN";
    
    $day = intval($i / 3);
    $slot = $i % 3;
    $hours = [9, 14, 19][$slot];
    $schedTime = date('Y-m-d H:i:s', strtotime("+{$day} days +{$hours} hours"));
    
    $d->query("INSERT INTO content_queue (type, title, content, source_post_id, `status`, scheduled_at) VALUES ('facebook', ?, ?, ?, 'pending', ?)", [$title, $fbContent, $p['id'], $schedTime]);
    $qCount++;
    echo "  ✅ FB: $title\n";
}
echo "Queued $qCount FB posts\n\n";

// 2. Add comments to newest posts
echo "=== Add comments ===\n";
$latest = $d->fetchAll("SELECT id FROM posts WHERE `status`='active' AND LENGTH(content) > 200 ORDER BY created_at DESC LIMIT 30");

$comments = [
    "Bài viết hay quá ae! 👍",
    "Cảm ơn ae chia sẻ, hữu ích lắm",
    "Save lại xem sau",
    "Đúng chuẩn luôn, mình cũng thấy vậy",
    "Mình áp dụng thấy hiệu quả thật",
    "Bổ sung: nên kiểm tra dầu máy nữa ae",
    "Mình chạy GHN thấy OK, phí 18-30k/đơn",
    "GHTK đơn nhiều hơn nhưng phí thấp hơn",
    "Chạy song song 2 hãng là best ae",
    "Mình cũng mới chuyển sang GHN, confirm phí cao hơn",
    "Khu mình cũng vậy, GPS hay sai lắm",
    "Cảm ơn ae, đọc xong thấy có động lực hơn 💪",
    "Tag ae @shipper xem có giống không 😂",
    "Mình ship khu này 2 năm rồi, đúng y chang",
    "Chia sẻ thêm: đường XYZ cũng hay kẹt buổi chiều",
];

$userIds = range(3, 102);
$cCount = 0;
foreach ($latest as $post) {
    shuffle($userIds);
    $num = rand(2, 4);
    for ($ci = 0; $ci < $num; $ci++) {
        $uid = $userIds[$ci];
        $cmtText = $comments[array_rand($comments)];
        $exists = $d->fetchOne("SELECT id FROM comments WHERE post_id = ? AND user_id = ? AND content = ?", [$post['id'], $uid, $cmtText]);
        if ($exists) continue;
        
        $hoursAgo = rand(0, 48);
        $d->query("INSERT INTO comments (post_id, user_id, content, likes_count, created_at) VALUES (?, ?, ?, ?, ?)", [
            $post['id'], $uid, $cmtText, rand(0, 10), date('Y-m-d H:i:s', strtotime("-{$hoursAgo} hours"))
        ]);
        $cCount++;
    }
}
echo "Added $cCount comments\n\n";

// Stats
$pending = $d->fetchOne("SELECT COUNT(*) c FROM content_queue WHERE `status`='pending'")['c'];
$totalComments = $d->fetchOne("SELECT COUNT(*) c FROM comments")['c'];
echo "📊 Total pending queue: $pending\n";
echo "📊 Total comments: $totalComments\n";
