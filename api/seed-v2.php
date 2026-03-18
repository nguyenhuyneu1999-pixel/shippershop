<?php
set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

// Get user IDs
$users = $db->fetchAll("SELECT id FROM users WHERE id > 10 AND `status`='active' ORDER BY RAND() LIMIT 100", []);
$userIds = array_map(function($u){return intval($u['id']);}, $users);
echo "Users: " . count($userIds) . "\n";

$imgDir = __DIR__ . '/../uploads/posts/';
if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);

// Download real images from picsum (various sizes/seeds for variety)
echo "\n=== Downloading images ===\n";
$newImages = [];
$seeds = [100,200,300,400,500,600,700,800,900,1000,111,222,333,444,555,666,777,888,999,123,456,789,147,258,369,159,267,348,135,246];
foreach ($seeds as $i => $seed) {
    $fname = "seed_v2_" . $i . ".jpg";
    $path = $imgDir . $fname;
    if (!file_exists($path)) {
        $w = rand(640, 900);
        $h = rand(400, 700);
        $data = @file_get_contents("https://picsum.photos/seed/{$seed}/{$w}/{$h}.jpg");
        if ($data && strlen($data) > 5000) {
            file_put_contents($path, $data);
            echo "  Downloaded: {$fname}\n";
        }
    }
    $newImages[] = "/uploads/posts/{$fname}";
}
echo count($newImages) . " images ready\n";

// Real YouTube videos about Vietnam shipping/delivery/landscape
$realVideos = [
    '/uploads/videos/vid_3_1772440538_b41b51fe.mp4',
    '/uploads/videos/vid_2_1772428966_bc9dc62b.mp4',
    '/uploads/videos/vid_3_1772415179_e1326d96.mp4',
];

// New unique posts
$posts = [
    // === PHONG CẢNH với ảnh ===
    ["Đường ship hôm nay đẹp quá trời! Chạy qua cầu Nhật Tân lúc hoàng hôn, nắng vàng rực rỡ. Nghề ship có cái hay là mỗi ngày 1 view khác nhau 🌅✨", "post", true, false],
    ["Giao đơn ở Tam Đảo sáng nay, sương mù dày đặc ko thấy đường. Chạy chậm lại thì thấy cảnh đẹp mê hồn. Ae xem ảnh đi 📸", "post", true, false],
    ["Bến Ninh Kiều Cần Thơ đêm lung linh quá! Ship xong đơn cuối rảnh rỗi ngồi uống cafe ngắm sông. Cuộc đời shipper cũng có lúc chill phết 🌙", "post", true, false],
    ["Chạy ship ở Phú Quốc mùa này đẹp lắm ae ơi. Biển xanh trong vắt, giao hàng cho resort mà muốn nghỉ luôn 😂🏖️", "post", true, false],
    ["Phố đi bộ Nguyễn Huệ lúc 5h sáng, vắng tanh. Ship hàng sớm mà được ngắm Sài Gòn yên bình thế này thì ok lắm", "post", true, false],
    ["Giao hàng ven sông Hương, Huế mùa này thơ mộng lắm. Cầu Tràng Tiền ban đêm lung linh, chụp lại cho ae xem 🌉", "post", true, false],
    ["Cánh đồng lúa ở Mù Cang Chải đang mùa nước đổ. Chạy ship qua đây mà quên cả đường về 🍃", "post", true, false],
    ["Ship ở khu vực Bãi Cháy, Quảng Ninh. View biển Hạ Long từ trên đồi đẹp ngất ngây luôn 🏔️🌊", "post", true, false],

    // === CONFESSION với ảnh ===
    ["Confession: Đang ship thì trời mưa to, dắt xe vào hiên nhà người ta trú. Bà chủ nhà ra mời vào uống trà nóng. Ấm lòng shipper 🥹☕", "post", true, false],
    ["Nói thật là có hôm ship 60 đơn mệt quá, về nhà nằm thẳng cẳng luôn. Nhưng nhìn con ngủ ngon lại thấy xứng đáng 💪", "post", true, false],
    ["Hôm nay là ngày ship cuối cùng trước khi nghỉ cưới. 3 năm chạy ship nuôi mộng cưới vợ, giờ thành hiện thực rồi ae ơi 🎉💒", "post", true, false],

    // === HỎI ĐÁP với hình ===
    ["Ae ơi cái app Ahamove bị lỗi thế này có ai biết sửa ko? Restart rồi mà vẫn bị. Đang có đơn mà app treo cứng luôn 😫", "question", true, false],
    ["Hỏi ae: Con đường này ở Thủ Đức có bị cấm xe tải ko? Hôm nay ship hàng cồng kềnh qua đây mà thấy sợ bị phạt", "question", true, false],
    ["Mn cho hỏi cái túi giữ nhiệt này mua ở đâu vậy? Thấy shipper khác dùng giữ đồ ăn nóng tốt lắm", "question", true, false],

    // === MẸO HAY với hình ===
    ["Mẹo ae: Dùng dây thun buộc hàng kiểu này chắc chắn hơn nhiều. Hàng ko bị rơi dù chạy nhanh. Ae thử đi 👌📦", "tip", true, false],
    ["Tips: Bọc điện thoại trong túi zip-lock khi trời mưa. Vẫn dùng GPS bình thường mà ko sợ hỏng máy 📱💧", "tip", true, false],
    ["Chia sẻ cách xếp hàng vào thùng ship hiệu quả nhất. Hàng nhỏ bỏ dưới, hàng lớn bỏ trên. Giao theo thứ tự ngược lại 📦", "tip", true, false],

    // === BÀI CÓ VIDEO ===
    ["Chia sẻ 1 ngày đi ship của mình từ sáng đến tối. Vất vả nhưng vui lắm ae ơi. Xem video để thấy 🎬", "post", false, true],
    ["Video quay lại cảnh giao hàng trong ngõ nhỏ Hà Nội. Đường hẹp + nhiều xe = cần kỹ năng lái cực tốt 🏍️", "post", false, true],
    ["Record lại lúc ship hàng gặp mưa giông bất ngờ. Tấp vào gầm cầu chờ + bọc hàng. Ae xem cho vui 🌧️", "post", false, true],

    // === REVIEW có ảnh ===
    ["Review áo khoác chống nắng mới mua trên Shopee 129k. Dùng 1 tuần rồi, mát thật sự, co giãn tốt, nhẹ. Recommend cho ae ship ☀️", "review", true, false],
    ["Review thùng ship loại mới của GHTK. To hơn, chắc hơn, có chia ngăn. Giao đồ ăn ko bị đổ nữa. 8/10 điểm 📦", "review", true, false],
    ["Review mũ bảo hiểm fullface AGU A208. Nhẹ, thoáng, có kính chống UV. Ship ngày nắng ko bị chói mắt. 350k xứng đáng 🏍️", "review", true, false],

    // === THẢO LUẬN ===
    ["Ae thấy GHTK hay J&T phí ship về tay mình nhiều hơn? Mình đang cân nhắc chuyển hãng. Xin ý kiến ae 🤔", "question", false, false],
    ["Hôm nay giao 1 đơn COD 5 triệu, khách kiểm hàng 20 phút mới nhận. Mình đứng chờ nắng cháy da. Ae có gặp trường hợp này ko?", "post", false, false],
    ["Vừa xong ca ship tối, 11h đêm mới về. Vợ nấu cơm chờ sẵn. Cảm động quá ae ơi. Động lực để ngày mai tiếp tục chiến 💪❤️", "post", false, false],
    ["Câu hỏi: Shipper có nên mua bảo hiểm tai nạn riêng ko? Ai đang dùng gói nào chia sẻ với nhé", "question", false, false],
    ["Haha hôm nay ship cho 1 ông khách vui tính, nhận hàng xong tặng mình cái bánh giò. Ấm bụng 😂🥟", "post", false, false],
    ["Ae khu Bình Dương ơi, đường Mỹ Phước Tân Vạn đang kẹt cứng. Tránh nhé! 🚗🚗🚗", "post", false, false],
    ["Giao hàng cho 1 em bé, em hỏi: Anh ship ơi sao anh đi nhiều thế? Mình trả lời: Vì anh muốn mang niềm vui đến cho mọi người 😊", "post", false, false],
    ["Ship ở khu chung cư mới, thang máy đông nghẹt. Chờ 15 phút mới lên được tầng 20. Ae có mẹo gì ko?", "question", false, false],
    ["Nhận đơn lúc 11h trưa nắng 39 độ. Chạy 8km giao hàng xong khách tip 20k. Vui hơn cả bonus tháng 😅", "post", false, false],
    ["Cuối tuần rồi, ae nào vẫn chạy ship giơ tay? ✋ Mình thì ngày nào cũng chạy, nghỉ ko quen 😂", "post", false, false],
    ["Sáng nay giao đơn đầu tiên lúc 6h sáng. Phố vắng, gió mát, chạy xe thấy thích ghê. Best time to ship 🌤️", "post", false, false],
    ["Ae nào biết khu vực Q7 HCM có chỗ nào đỗ xe free cho shipper ko? Gửi xe mỗi lần 5k tốn lắm", "question", false, false],
    ["Tổng kết tuần: 280 đơn, thu nhập 4.2tr. Tuần sau cố lên 300 đơn! Keep going ae 📈💰", "post", false, false],
    ["Mưa to quá! Ae nhớ kiểm tra hàng, bọc kỹ nha. Hàng ướt = đánh giá 1 sao = mất thưởng 🌧️📦", "post", false, false],
    ["Hôm nay có khách nhắn: Cảm ơn anh ship giao nhanh quá! Chỉ 1 tin nhắn nhỏ mà vui cả ngày luôn 💙", "post", false, false],
    ["Ship đồ ăn 3km mà khách tip 50k. Trời ơi dễ thương quá! Gặp khách thế này mỗi ngày thì giàu rồi 😄", "post", false, false],
    ["Ae ơi có ai gặp bug app GHN hôm nay ko? Đơn báo giao rồi nhưng app vẫn hiện đang giao. Loạn hết 🐛", "question", false, false],
    ["3 năm làm shipper, điều mình học được: Kiên nhẫn, lễ phép, và luôn cười. Khách hàng cảm nhận được thái độ của mình ❤️", "post", false, false],
    ["Review: Bao tay chống nắng trên Lazada 45k. Chất vải mỏng nhẹ, chống UV tốt. Ship mùa hè essential 🧤☀️", "review", false, false],
];

$provinces = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','Bắc Ninh','Hải Dương','Bình Dương','Đồng Nai','Long An','Thanh Hóa','Nghệ An','Lào Cai','Quảng Ninh','Thái Nguyên'];

$commentPool = [
    'Chuẩn ae 👍','Hay quá','Đúng r ae ơi','Like!','Cảm ơn ae','Follow luôn','Ghê thật','Giỏi quá ae',
    'Mình cũng vậy nè','Ae pro lắm','Chia sẻ hay quá','Ghi nhớ','Thanks ae 🙏','Quá đúng luôn',
    'Haha đúng rồi 😂','Kinh nghiệm thực tế ae','Ae ship hãng nào?','Khu mình cũng thế','Mong ae nhiều đơn',
    'Ae cố lên nha 💪','Respect ae','Mình mới biết luôn','Ae chia sẻ thêm đi','Bổ ích ghê',
    'Ae có kinh nghiệm thiệt','Ship bao lâu rồi ae?','Khu nào vậy ae?','Ảnh đẹp quá','View đỉnh thật 📸',
    'Ae khéo chụp quá','Video hay ae','Thấy thương ae quá 🥺','Đọc mà thấy cảm động','Ae viết hay ghê',
    'Cùng cảnh ngộ nè ae','Mình confirm luôn','Ae nói chuẩn ko cần chỉnh','Real talk đó ae',
];

echo "\n=== Creating new posts ===\n";
$createdPosts = 0;
$imgIdx = 0;

foreach ($posts as $i => $p) {
    $content = $p[0];
    $type = $p[1];
    $hasImg = $p[2];
    $hasVid = $p[3];
    $uid = $userIds[array_rand($userIds)];
    $province = $provinces[array_rand($provinces)];
    $daysAgo = rand(0, 14);
    $hoursAgo = rand(0, 23);
    $minsAgo = rand(0, 59);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours -{$minsAgo} minutes"));

    $images = '[]';
    if ($hasImg && count($newImages) > 0) {
        $n = rand(1, 3);
        $selected = [];
        for ($j = 0; $j < $n; $j++) {
            $selected[] = $newImages[$imgIdx % count($newImages)];
            $imgIdx++;
        }
        $images = json_encode($selected);
    }

    $videoUrl = null;
    if ($hasVid && count($realVideos) > 0) {
        $videoUrl = $realVideos[array_rand($realVideos)];
    }

    try {
        $db->query("INSERT INTO posts (user_id,content,images,video_url,type,province,`status`,created_at,likes_count,comments_count,views) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$uid, $content, $images, $videoUrl, $type, $province, 'active', $createdAt, 0, 0, rand(20, 800)]);
        $pid = $db->fetchOne("SELECT MAX(id) as m FROM posts", []);
        $postId = intval($pid['m'] ?? 0);

        // Add likes
        $numLikes = rand(3, 30);
        $likers = array_slice($userIds, 0, min($numLikes, count($userIds)));
        shuffle($likers);
        $likers = array_slice($likers, 0, $numLikes);
        foreach ($likers as $liker) {
            try { $db->query("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,?)", [$postId, $liker, $createdAt]); } catch (Throwable $e) {}
        }
        $lkCnt = $db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?", [$postId]);
        $db->query("UPDATE posts SET likes_count=? WHERE id=?", [intval($lkCnt['c'] ?? 0), $postId]);

        // Add comments
        $numCmts = rand(2, 8);
        for ($c = 0; $c < $numCmts; $c++) {
            $cmtUser = $userIds[array_rand($userIds)];
            $cmtContent = $commentPool[array_rand($commentPool)];
            $cmtTime = date('Y-m-d H:i:s', strtotime($createdAt . " + " . rand(1, 48) . " hours"));
            try { $db->query("INSERT INTO comments (post_id,user_id,content,`status`,created_at,likes_count) VALUES (?,?,?,?,?,?)", [$postId, $cmtUser, $cmtContent, 'active', $cmtTime, rand(0, 8)]); } catch (Throwable $e) {}
        }
        $cmCnt = $db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id=? AND `status`='active'", [$postId]);
        $db->query("UPDATE posts SET comments_count=? WHERE id=?", [intval($cmCnt['c'] ?? 0), $postId]);

        $createdPosts++;
        $imgTag = $hasImg ? '🖼️' : ($hasVid ? '🎬' : '📝');
        echo "  {$imgTag} Post #{$i} [ID={$postId}] likes=" . intval($lkCnt['c']??0) . " cmts=" . intval($cmCnt['c']??0) . " : " . mb_substr($content, 0, 45) . "...\n";
    } catch (Throwable $e) {
        echo "  SKIP #{$i}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Created {$createdPosts} new posts ===\n";

// Final count
$total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
echo "Total active posts: " . $total['c'] . "\n";
echo "DONE!\n";
