<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// Get 20 newest real posts (long content)
$posts = $d->fetchAll("SELECT id, content FROM posts WHERE `status`='active' AND LENGTH(content) > 150 ORDER BY created_at DESC LIMIT 20");

$comments = [
    // Generic positive
    ["Bài viết hay quá ae!", "Cảm ơn ae chia sẻ 👍", "Hữu ích lắm!", "Save lại xem sau", "Đúng chuẩn luôn ae"],
    // Tips specific
    ["Mình áp dụng mẹo 1 thấy hiệu quả thật", "Bổ sung: nên kiểm tra dầu máy nữa ae", "Mình cũng tiết kiệm được kha khá nhờ mấy tips này"],
    // Question answers  
    ["Mình chạy GHN thấy OK, phí 18-30k/đơn tùy quận", "GHTK đơn nhiều hơn nhưng phí thấp hơn", "Chạy song song 2 hãng là best ae"],
    // Emotional
    ["Cảm ơn ae, đọc xong thấy có động lực hơn 💪", "Đời ship là vậy, cố gắng thôi ae ơi", "Tag ae @shipper xem có giống không 😂"],
    // Area specific
    ["Khu mình cũng vậy, GPS hay sai lắm", "Mình ship khu này 2 năm rồi, đúng y chang", "Bổ sung: đường ABC cũng hay kẹt buổi chiều"],
];

$userIds = range(3, 102);
$count = 0;

foreach ($posts as $pi => $post) {
    shuffle($userIds);
    // 3-5 comments per post
    $numCmts = rand(3, 5);
    $group = $pi % count($comments);
    
    for ($ci = 0; $ci < $numCmts; $ci++) {
        $cGroup = ($group + $ci) % count($comments);
        $cIndex = $ci % count($comments[$cGroup]);
        $cmtText = $comments[$cGroup][$cIndex];
        
        $uid = $userIds[$ci];
        $hoursAgo = rand(0, 72);
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$hoursAgo} hours"));
        $likes = rand(0, 15);
        
        $exists = $d->fetchOne("SELECT id FROM comments WHERE post_id = ? AND user_id = ? AND content = ?", [$post['id'], $uid, $cmtText]);
        if ($exists) continue;
        
        try {
            $d->query("INSERT INTO comments (post_id, user_id, content, likes_count, created_at) VALUES (?, ?, ?, ?, ?)", [
                $post['id'], $uid, $cmtText, $likes, $createdAt
            ]);
            $count++;
        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }
    }
    echo "✅ Post #{$post['id']}: added comments\n";
}

echo "\n🎉 Inserted $count real comments!\n";
