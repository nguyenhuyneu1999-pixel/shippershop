<?php
set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

// Test insert first
echo "TEST: ";
try {
    $tid = $db->insert('posts', ['user_id'=>2, 'content'=>'seedtest', 'type'=>'post', 'status'=>'active']);
    echo "OK id=$tid\n";
    $db->query("DELETE FROM posts WHERE id=?", [$tid]);
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . " LINE:" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit;
}

// Get existing seed users
$users = $db->fetchAll("SELECT id, fullname FROM users WHERE email LIKE '%@shippershop.vn' ORDER BY id LIMIT 100", []);
echo "Seed users: " . count($users) . "\n";
if (count($users) < 10) { echo "Not enough users\n"; exit; }
$uids = array_column($users, 'id');

// Posts content
$posts = [
    "Sáng nay chạy đơn qua Hội An, nắng đẹp quá ae ơi 🌅",
    "Ship hàng lên Sa Pa gặp mây mù dày đặc, ae cẩn thận nha",
    "Đường lên Đà Lạt hôm nay đẹp lắm, 2 bên toàn hoa dã quỳ vàng rực 📸",
    "Qua cầu Rồng Đà Nẵng lúc đêm, phun lửa đẹp vãi",
    "Giao hàng ở Ninh Bình, tiện ghé Tràng An chụp vài tấm 🏞️",
    "Chạy đơn dọc biển Quy Nhơn, gió mát quá trời",
    "Sáng nay ship ở Phú Quốc, biển trong xanh cực kỳ 🐟",
    "Ruộng bậc thang Mù Cang Chải mùa lúa chín vàng óng",
    "Hà Giang loop ngày thứ 3, đèo Mã Pí Lèng choáng ngợp 😍",
    "Giao đơn ở Huế, ghé Đại Nội chụp ảnh rêu phong đẹp lạ",
    "Ae ơi cho hỏi GHTK giờ tính phí thế nào? Phí ship tăng gần đây",
    "J&T bây giờ giao nhanh hơn trước nhiều. Đơn HN-SG 2 ngày tới luôn 👍",
    "Có ae nào đang chạy GHN không? Cơ chế thưởng tháng này thế nào",
    "Viettel Post mới ra chính sách mới, ae nào biết chia sẻ nhé",
    "So sánh phí ship SPX và GHTK, bên nào rẻ hơn ae?",
    "Hôm nay giao 50 đơn COD, mệt nhưng vui. Cố gắng đạt target 🎯",
    "Khách hàng báo hàng bị móp, mà mình giao cẩn thận lắm rồi. Xử lý sao ae?",
    "Tips giao hàng mùa mưa: bọc hàng 2 lớp nilon, mang áo mưa dự phòng 🌧️",
    "Mới chuyển từ J&T sang GHN, app GHN dễ dùng hơn nhiều",
    "Shipper mới vào nghề cần chú ý gì ae? Em mới chạy được 1 tuần",
    "Hôm nay giao nhầm đơn, may mà 2 khách ở gần nhau 😅",
    "Nghề ship tuy vất vả nhưng tự do hơn đi làm văn phòng nhiều",
    "Mưa to quá vẫn phải giao, khách tip thêm 20k ấm lòng 🥺",
    "Có ngày chạy 6h sáng đến 10h đêm mà chỉ được 300k. Vất vả thật",
    "Hôm qua bị chó cắn khi giao hàng, may chủ nhà đền tiền tiêm phòng",
    "Ae có bao giờ ship đơn xong rồi khách hủy không? Mình bị 3 lần rồi",
    "Mình là nữ shipper, nhiều người nhìn ngạc nhiên lắm 💪",
    "Giao hàng lên tầng 20 không thang máy, muốn xỉu 🥵",
    "Ship 1 năm rồi, giờ thuộc đường Sài Gòn hơn Google Maps",
    "Nghề ship dạy mình kiên nhẫn, chịu khó, trân trọng đồng tiền",
    "Ae thấy nghề ship 5 năm nữa sẽ thế nào? Có bị robot thay ko? 🤖",
    "Đâu là khu vực khó giao nhất VN? Vote nội thành HN giờ cao điểm",
    "Chia sẻ playlist nhạc ae hay nghe khi chạy ship 🎵",
    "Ae dùng điện thoại gì chạy ship? Pin trâu nhất là dòng nào?",
    "Đường VN ngày càng đông, ae có mẹo tránh kẹt xe ko?",
    "Ae ăn gì khi chạy ship? Chia sẻ quán ngon giá rẻ ven đường 🍜",
    "Review găng tay chống nắng Shopee, dùng 2 tháng vẫn ok 👍",
    "Mới mua thùng giữ nhiệt, đồ ăn giao tới nơi vẫn nóng hổi",
    "Review áo mưa 2 lớp, chạy mưa to không thấm",
    "Sáng nay trời đẹp quá, chúc ae ngày mới nhiều đơn 💪",
    "Ae ơi đường Nguyễn Trãi đang kẹt cứng, tránh đi đường khác nha",
    "Ship xong đơn cuối ngày, về nhà nghỉ ngơi. Good night ae 🌙",
    "Khách tip 50k vì giao sớm, vui quá ae ơi 🎉",
    "Có ae nào ở Bình Dương không? Mình mới chuyển về chưa quen đường",
    "Luôn chụp ảnh hàng trước khi giao để có bằng chứng ae nhé",
    "Hôm nay giao 72 đơn, kỷ lục mới! 💪🔥",
    "Ae nào biết chỗ sửa xe uy tín ở quận 7 ko?",
    "Trời nắng 40 độ mà vẫn phải chạy, uống nước nhiều ae 🥤",
    "Ship 3 năm tích được ít vốn mở quán trà sữa nhỏ 🧋",
    "Đèo Hải Vân mây trắng bao phủ, cung đường đẹp nhất VN",
    "Vịnh Hạ Long hàng ngàn hòn đảo, kỳ quan không nói chơi",
    "Chợ Bến Thành chiều tấp nập, nhộn nhịp đặc trưng miền Nam 🏙️",
    "Phong Nha Kẻ Bàng hang động đẹp siêu thực ae ơi",
    "Mùa Tết ae tính chạy hay nghỉ? Phí ship Tết cao lắm",
    "Bảo hiểm cho shipper ae có mua không? Nên mua loại nào?",
    "Grab Express thu nhập 1 ngày được bao nhiêu ae?",
    "App GHTK bị lỗi không nhận đơn được, ai bị giống ko?",
    "Hàng dễ vỡ ae giao thế nào cho an toàn?",
    "Ninja Van tuyển shipper khu vực Bình Dương, ai biết info cho mình",
    "Bên BEST Express có ai đang chạy ko? Phí khá cạnh tranh",
    "Đường Cầu Giấy sáng nay ngập nặng, ae tránh nha",
    "Xe Wave Alpha có phù hợp chạy ship không? Mình tính mua",
    "Weekend vẫn phải chạy ship nhưng đường thoáng cũng thích 😌",
    "Review quán bún bò ngon Lê Văn Sỹ, 35k/tô ngon xuất sắc 🍜",
    "Mới vào nghề 1 tháng, cảm ơn ae đã giúp đỡ 🥰",
    "Đèo Ô Quy Hồ sương mù dày, ae chạy Lào Cai cẩn thận",
    "Ai muốn đổi ca ship khu Thủ Đức ko? Mình cần ca sáng",
    "Giao hàng cho nhà giàu, nhìn biệt thự mà ước mơ 💪",
    "Tổng kết tháng: 1500 đơn, thu nhập 18tr. Cố gắng hơn 📈",
    "Bãi biển Mỹ Khê lúc bình minh, yên bình quá đi",
    "Review sạc dự phòng 20000mAh cho ship, 250k xài 3 tháng tốt",
    "Điện thoại Redmi Note 13 pin trâu, chạy cả ngày không lo",
    "Sáng nay ship ở Đà Lạt, thời tiết mát mẻ dễ chịu quá 🌿",
    "Ae chia sẻ kinh nghiệm ship đồ ăn nhanh đi. Hay bị khách chê nguội",
    "Cảnh báo: đường tránh quốc lộ 1A đoạn Bình Định đang sửa, kẹt nặng",
    "Ship 2 năm giờ quen hết khách trong khu, họ gọi tên mình luôn 😊",
    "Ae có biết app nào track thu nhập hàng ngày không? Mình cần quản lý chi tiêu",
    "Khu công nghiệp Bắc Ninh đông shipper quá, cạnh tranh dữ dội",
    "Sáng nay được khách cho ổ bánh mì, đơn giản mà vui 😄",
    "Ae ship COD nhớ kiểm tiền kỹ, mình bị nhận tiền giả 1 lần rồi",
    "Giao hàng ở phố cổ Hà Nội, đường nhỏ xe đông, skill xử lý cao 🏍️",
    "Cầu Cần Thơ ban đêm lung linh ánh đèn, đẹp mê ae ơi",
    "Biển Nha Trang trong veo, giao xong đơn tắm biển 1 chút 🏖️",
    "Ae nào chạy đêm ko? Đêm đơn ít nhưng đường thoáng",
    "Mùa hè chạy ship cực quá, mồ hôi ướt áo nhưng cố gắng ae 💦",
    "Review balo ship hàng chống nước, mua Lazada 150k, dùng tốt",
    "Đường phố Sài Gòn lúc 5h sáng, vắng vẻ yên bình. Thích cảm giác này",
    "Ae ơi xăng tăng giá lại rồi, chạy ship giờ lợi nhuận mỏng quá",
    "Confession: có khách quen hay tặng nước, giao cho bạn ấy mình vui nhất",
    "Đồng Tháp mùa nước nổi, đường ngập nhưng cảnh đẹp phải nói 🌾",
    "Ae có thấy shipper mình cần tập thể dục không? Ngồi xe cả ngày đau lưng",
    "Tây Nguyên mùa cà phê, chạy qua rẫy thơm lừng. Việt Nam mình đẹp 🏔️",
    "Giao 1 đơn ở chung cư tầng 30, view nhìn xuống mà rợn luôn ae 😂",
    "Chia sẻ: mua bảo hiểm xe 2 chiều nhé ae, 500k/năm mà an tâm hơn nhiều",
    "Sa Pa mùa đông sương mù + lạnh, nhưng view từ Fansipan thì hết nước chấm",
    "Phú Yên biển xanh cát trắng, giao đơn xong ở lại chơi luôn 😎",
    "Ae có bao giờ bị lạc đường ở khu đô thị mới không? Mình bị hoài 😂",
    "Cuối năm đơn nhiều, ae tranh thủ kiếm thêm tiền tiêu Tết nhé 🧧",
    "Cảm ơn ShipperShop cho anh em có nơi chia sẻ. Yêu thương ae ❤️",
];

$provs = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','Bắc Ninh','Quảng Ninh','Thanh Hóa','Nghệ An','Lào Cai'];
$types = ['post','post','post','review','question','tip','post','post','question','post'];

// Get existing post images
$existImgs = glob(__DIR__ . '/../uploads/posts/seed_land_*.jpg');
$imgPaths = [];
foreach ($existImgs as $f) $imgPaths[] = '/uploads/posts/' . basename($f);

echo "Available images: " . count($imgPaths) . "\n";
echo "Creating " . count($posts) . " posts...\n";

$created = 0;
foreach ($posts as $i => $content) {
    $uid = $uids[array_rand($uids)];
    $type = $types[$i % count($types)];
    $prov = $provs[array_rand($provs)];
    $hrs = rand(0, 168);
    $mins = rand(0, 59);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$hrs} hours -{$mins} minutes"));
    $images = null;
    if ($i < count($imgPaths) && count($imgPaths) > 0) {
        $images = json_encode([$imgPaths[$i % count($imgPaths)]]);
    }

    try {
        $pid = $db->insert('posts', [
            'user_id' => $uid,
            'content' => $content,
            'images' => $images,
            'type' => $type,
            'province' => $prov,
            'status' => 'active',
            'created_at' => $createdAt
        ]);
        $created++;
        if ($created % 10 == 0) echo "Created $created posts...\n";
    } catch (Throwable $e) {
        echo "ERR post $i: " . $e->getMessage() . "\n";
    }
}
echo "Posts created: $created\n";

// Comments
echo "\nCreating comments...\n";
$allPostIds = $db->fetchAll("SELECT id FROM posts WHERE `status`='active' ORDER BY created_at DESC LIMIT 100", []);
$pids = array_column($allPostIds, 'id');

$cmtTexts = [
    'Đúng quá ae, mình cũng gặp tương tự',
    'Cảm ơn ae chia sẻ 👍',
    'Mình ủng hộ ae',
    'Hay quá, follow rồi nha',
    'Ảnh đẹp quá ae 📸',
    'Cố lên ae, nghề ship tuy cực nhưng vui',
    'Cảm ơn ae, note lại rồi',
    'Haha đúng rồi 😂',
    'Vote cho ae 🔥',
    'Mình cũng nghĩ vậy ạ',
    'Kinh nghiệm quý giá, cảm ơn ae',
    'Ở SG khu nào cũng kẹt xe hết ae ơi 😅',
    'Like mạnh, bài hay quá',
    'Good luck ae! Chúc nhiều đơn 🍀',
    'Save bài này rồi, hữu ích quá',
    'Ae viết hay quá, mong chia sẻ thêm',
    'Tháng trước mình cũng gặp luôn',
    'Pro quá ae, cần học hỏi nhiều',
    'Chia sẻ thêm kinh nghiệm nhé ae 🙏',
    'Real talk đấy, ai trong nghề mới hiểu',
];

$cmtCount = 0;
foreach ($pids as $pid) {
    $n = rand(2, 6);
    for ($c = 0; $c < $n; $c++) {
        $cuid = $uids[array_rand($uids)];
        $txt = $cmtTexts[array_rand($cmtTexts)];
        $ctime = date('Y-m-d H:i:s', strtotime('-' . rand(0, 72) . ' hours'));
        try {
            $db->insert('comments', ['post_id'=>$pid, 'user_id'=>$cuid, 'content'=>$txt, 'status'=>'active', 'created_at'=>$ctime]);
            $cmtCount++;
        } catch (Throwable $e) {}
    }
}
echo "Comments: $cmtCount\n";

// Likes
echo "\nCreating likes...\n";
$lkCount = 0;
foreach ($pids as $pid) {
    $n = rand(2, 20);
    shuffle($uids);
    foreach (array_slice($uids, 0, $n) as $luid) {
        try { $db->query("INSERT IGNORE INTO post_likes (post_id, user_id, created_at) VALUES (?,?,NOW())", [$pid, $luid]); $lkCount++; } catch (Throwable $e) {}
        try { $db->query("INSERT IGNORE INTO likes (post_id, user_id, created_at) VALUES (?,?,NOW())", [$pid, $luid]); } catch (Throwable $e) {}
    }
}
echo "Likes: ~$lkCount\n";

// Follows
echo "\nCreating follows...\n";
$flCount = 0;
foreach ($uids as $uid) {
    shuffle($uids);
    foreach (array_slice($uids, 0, rand(5, 15)) as $fuid) {
        if ($fuid == $uid) continue;
        try { $db->query("INSERT IGNORE INTO follows (follower_id, following_id, created_at) VALUES (?,?,NOW())", [$uid, $fuid]); $flCount++; } catch (Throwable $e) {}
    }
}
echo "Follows: ~$flCount\n";

echo "\n=== DONE! ===\n";
