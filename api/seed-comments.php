<?php
set_time_limit(120);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

$userIds = array_map(function($u){return intval($u['id']);}, $db->fetchAll("SELECT id FROM users WHERE id > 10 ORDER BY id ASC LIMIT 100", []));
$postIds = array_map(function($p){return intval($p['id']);}, $db->fetchAll("SELECT id FROM posts WHERE `status`='active' ORDER BY id DESC LIMIT 120", []));

echo "Users: " . count($userIds) . " Posts: " . count($postIds) . "\n\n";

$commentContents = [
    'Chuẩn luôn ae ơi 👍','Đúng vậy, mình cũng gặp rồi','Like mạnh!','Cảm ơn chia sẻ nha','Hay quá, note lại 📝',
    'Mình cũng nghĩ vậy','Chia sẻ hữu ích quá','Bài hay, follow luôn','Cảm ơn ae nhiều nha','Kinh nghiệm thực tế 👏',
    'Đồng ý 100%','Mình ship GHTK, confirm luôn','À thì ra vậy, cảm ơn nha','Ae pro quá','Ghi nhớ 💡',
    'Ship bao lâu rồi ae?','Cùng cảnh ngộ luôn 😅','Khu mình cũng vậy nè','Mình thử rồi, hiệu quả thật','Ae giỏi quá',
    'Ước gì mình cũng được vậy','Thiệt hả? Để thử coi','Cảm ơn info ae 🙏','Hay lắm, share cho anh em','Mong sớm cải thiện',
    'Ai ở HCM cho mình hỏi thêm','Khu Hà Nội có khác ko ae?','Mình mới vào nghề, học hỏi nhiều','Bữa mình cũng bị y chang 😤',
    'Chia sẻ thêm đi ae','Ae nói đúng quá','Haha đúng rồi 😂','Ship vui mà ae','Cố lên nào 💪','An toàn nhé ae 🤞',
    'Thông tin bổ ích ghê','Lần đầu biết luôn á','Thanks ae','Có link ko ae?','Mình ghi nhớ rồi nè',
    'Real ae, mình trải nghiệm rồi','Bổ sung thêm: nên mang thêm khăn lau','Ae chạy hãng nào vậy?','Giao hàng ở đâu ae?',
    'Chụp đẹp quá ae 📸','Cảnh đẹp vậy mà còn phải ship 😂','Đẹp quá trời','Ở đâu vậy ae?','Muốn đi ghê',
    'Mình cũng muốn thử','Ae dùng xe gì vậy?','Xăng bao nhiêu 1 ngày ae?','Thu nhập 1 ngày bao nhiêu?','Chia sẻ thêm tip đi ae',
];

$commentCount = 0;
foreach ($postIds as $pid) {
    $numComments = rand(2, 8);
    for ($c = 0; $c < $numComments; $c++) {
        $uid = $userIds[array_rand($userIds)];
        $ct = $commentContents[array_rand($commentContents)];
        $daysAgo = rand(0, 15);
        $hoursAgo = rand(0, 23);
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
        try {
            $db->query("INSERT INTO comments (post_id,user_id,content,`status`,created_at,likes_count) VALUES (?,?,?,?,?,?)",
                [$pid, $uid, $ct, 'active', $createdAt, rand(0, 10)]);
            $commentCount++;
        } catch (Throwable $e) {}
    }
    // Update count
    try {
        $cnt = $db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id=? AND `status`='active'", [$pid]);
        $db->query("UPDATE posts SET comments_count=? WHERE id=?", [$cnt['c'] ?? 0, $pid]);
    } catch (Throwable $e) {}
}

// Also update likes_count from post_likes table
foreach ($postIds as $pid) {
    try {
        $cnt = $db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?", [$pid]);
        $db->query("UPDATE posts SET likes_count=? WHERE id=?", [$cnt['c'] ?? 0, $pid]);
    } catch (Throwable $e) {}
}

echo "Created {$commentCount} comments\n";
echo "Updated likes_count for " . count($postIds) . " posts\n";
echo "DONE!\n";
