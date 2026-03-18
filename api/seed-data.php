<?php
// SEED SCRIPT: 100 Vietnamese users + 100 posts + comments
// Run once then delete
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

$db = db();

// ================================================
// 1. VIETNAMESE NAMES (50 male + 50 female)
// ================================================
$maleFirst = ['Minh','Hùng','Đức','Thành','Tuấn','Hoàng','Nam','Long','Phúc','Quang','Dũng','Bảo','Khoa','Tùng','Vinh','Hải','Trung','Kiên','Tú','Thiện','Nhật','Toàn','Khánh','Sơn','Đạt'];
$femaleFirst = ['Linh','Hương','Thảo','Ngọc','Trang','Hà','Mai','Lan','Yến','Phương','Vy','Nhi','Trâm','Quyên','Thy','Uyên','Diệu','Hạnh','Châu','Thùy','Ngân','Trinh','Hiền','Vân','Quỳnh'];
$middleMale = ['Văn','Đình','Quốc','Xuân','Thanh','Công','Minh','Đức','Hữu','Ngọc'];
$middleFemale = ['Thị','Ngọc','Phương','Thanh','Bích','Minh','Kim','Hoàng','Thu','Diệu'];
$lastNames = ['Nguyễn','Trần','Lê','Phạm','Hoàng','Huỳnh','Phan','Vũ','Võ','Đặng','Bùi','Đỗ','Hồ','Ngô','Dương','Lý','Lưu','Trịnh','Đinh','Tô'];

$shippingCompanies = ['GHTK','J&T','GHN','Viettel Post','SPX','Ninja Van','BEST','Ahamove','Grab','Be'];
$provinces = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','Bắc Ninh','Quảng Ninh','Thanh Hóa','Nghệ An','Lào Cai','Lâm Đồng','Khánh Hòa','Bình Dương','Đồng Nai','Long An','Thái Nguyên','Nam Định','Hưng Yên','Vĩnh Phúc','Bắc Giang'];

// Avatar URLs from free sources
$maleAvatars = [];
$femaleAvatars = [];
for ($i = 1; $i <= 50; $i++) {
    $maleAvatars[] = "https://randomuser.me/api/portraits/men/" . ($i + 10) . ".jpg";
    $femaleAvatars[] = "https://randomuser.me/api/portraits/women/" . ($i + 10) . ".jpg";
}

echo "=== CREATING 100 USERS ===\n";
$userIds = [];
$userNames = [];

for ($i = 0; $i < 100; $i++) {
    $isMale = $i < 50;
    $lastName = $lastNames[array_rand($lastNames)];
    if ($isMale) {
        $middle = $middleMale[array_rand($middleMale)];
        $first = $maleFirst[$i % count($maleFirst)];
    } else {
        $middle = $middleFemale[array_rand($middleFemale)];
        $first = $femaleFirst[($i - 50) % count($femaleFirst)];
    }
    $fullname = "$lastName $middle $first";

    // Download avatar
    $avatarUrl = $isMale ? $maleAvatars[$i % 50] : $femaleAvatars[($i - 50) % 50];
    $avatarPath = null;
    $avatarDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

    $fname = 'seed_' . ($i + 100) . '.jpg';
    $localPath = $avatarDir . $fname;
    if (!file_exists($localPath)) {
        $imgData = @file_get_contents($avatarUrl);
        if ($imgData) {
            file_put_contents($localPath, $imgData);
            $avatarPath = '/uploads/avatars/' . $fname;
        }
    } else {
        $avatarPath = '/uploads/avatars/' . $fname;
    }

    $username = strtolower(str_replace(' ', '', $first)) . rand(100, 999);
    $email = $username . '@shippershop.vn';
    $ship = $shippingCompanies[array_rand($shippingCompanies)];
    $prov = $provinces[array_rand($provinces)];
    $bio = '';
    $bios = [
        'Shipper tự do, giao hàng tận tâm 🚛',
        'Ship COD toàn quốc, uy tín số 1 💪',
        'Giao hàng nhanh - an toàn - tiết kiệm',
        'Shipper ' . $prov . ' | ' . $ship,
        'Đam mê nghề ship, yêu đường phố Việt Nam 🏍️',
        'Ship hàng ' . $prov . ' và lân cận',
        'Giao hàng là đam mê, khách hàng là thượng đế',
        'Rider ' . $ship . ' | Chuyên tuyến ' . $prov,
        'Shipper chuyên nghiệp, nhận ship mọi loại hàng',
        ''
    ];
    $bio = $bios[array_rand($bios)];

    $passHash = password_hash('shipper123', PASSWORD_DEFAULT);
    $joinDate = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));

    try {
        $db->query("INSERT INTO users (fullname, username, email, password, avatar, shipping_company, bio, created_at) VALUES (?,?,?,?,?,?,?,?)",
            [$fullname, $username, $email, $passHash, $avatarPath, $ship, $bio, $joinDate]);
        $uid = $db->getLastInsertId();
        $userIds[] = $uid;
        $userNames[$uid] = $fullname;
        echo "User $uid: $fullname ($ship)\n";
    } catch (Throwable $e) {
        echo "Skip user $fullname: " . $e->getMessage() . "\n";
    }

    if ($i % 10 === 0) usleep(200000); // throttle avatar downloads
}

echo "\nCreated " . count($userIds) . " users\n";
if (count($userIds) < 10) { echo "Not enough users, stopping.\n"; exit; }

// ================================================
// 2. LANDSCAPE IMAGES (from Unsplash - Vietnam)
// ================================================
$landscapeImgs = [
    'https://images.unsplash.com/photo-1528127269322-539801943592?w=800',
    'https://images.unsplash.com/photo-1555921015-5532091f6026?w=800',
    'https://images.unsplash.com/photo-1573790387438-4da905039392?w=800',
    'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=800',
    'https://images.unsplash.com/photo-1557750255-c76072a7aad1?w=800',
    'https://images.unsplash.com/photo-1540162632300-c5837ce5e78d?w=800',
    'https://images.unsplash.com/photo-1464852045489-bccb7d17fe39?w=800',
    'https://images.unsplash.com/photo-1552553302-9211bf7f7053?w=800',
    'https://images.unsplash.com/photo-1580674684081-7617fbf3d745?w=800',
    'https://images.unsplash.com/photo-1504457047772-27faf1c00561?w=800',
    'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=800',
    'https://images.unsplash.com/photo-1513415756790-2ac1db1297d0?w=800',
    'https://images.unsplash.com/photo-1501526029524-a8ea952938a2?w=800',
    'https://images.unsplash.com/photo-1570366583862-f91883984fde?w=800',
    'https://images.unsplash.com/photo-1535581652167-3a26c90481cf?w=800',
];

echo "\n=== DOWNLOADING POST IMAGES ===\n";
$postImgDir = __DIR__ . '/../uploads/posts/';
$localImgs = [];
foreach ($landscapeImgs as $idx => $url) {
    $fname = 'seed_land_' . ($idx + 1) . '.jpg';
    $localPath = $postImgDir . $fname;
    if (!file_exists($localPath)) {
        $data = @file_get_contents($url);
        if ($data && strlen($data) > 5000) {
            file_put_contents($localPath, $data);
            $localImgs[] = '/uploads/posts/' . $fname;
            echo "Downloaded: $fname (" . round(strlen($data)/1024) . "KB)\n";
        } else {
            echo "Skip: $fname (too small or failed)\n";
        }
    } else {
        $localImgs[] = '/uploads/posts/' . $fname;
        echo "Exists: $fname\n";
    }
    usleep(300000);
}

// ================================================
// 3. CREATE 100 POSTS
// ================================================
echo "\n=== CREATING 100 POSTS ===\n";

// Realistic Vietnamese content
$landscapePosts = [
    "Sáng nay chạy đơn qua Hội An, nắng đẹp quá ae ơi. Phố cổ lúc sáng sớm vắng người, đẹp không tả nổi 🌅",
    "Ship hàng lên Sa Pa gặp mây mù dày đặc, tầm nhìn 5m luôn. Ae nào chạy tuyến này cẩn thận nha",
    "Đường lên Đà Lạt hôm nay đẹp lắm, 2 bên toàn hoa dã quỳ vàng rực. Chạy xe mà cứ muốn dừng chụp ảnh 📸",
    "Qua cầu Rồng Đà Nẵng lúc đêm, phun lửa phun nước đẹp vãi. Lần đầu thấy luôn ae ạ",
    "Giao hàng ở Ninh Bình, tiện ghé Tràng An chụp vài tấm. Thiên nhiên VN mình đẹp thật 🏞️",
    "Chạy đơn dọc biển Quy Nhơn, gió mát quá trời. Ae nào chưa đi thì nên đi 1 lần",
    "Sáng nay ship ở Phú Quốc, biển trong xanh cực kỳ. Nước trong nhìn thấy cá luôn 🐟",
    "Ruộng bậc thang Mù Cang Chải mùa lúa chín vàng óng. Đẹp nhất VN là đây chứ đâu",
    "Hà Giang loop ngày thứ 3, đèo Mã Pí Lèng nhìn xuống sông Nho Quế. Choáng ngợp luôn ae 😍",
    "Giao đơn ở Huế, ghé Đại Nội chụp ảnh. Kiến trúc cổ kính, rêu phong đẹp lạ",
    "Bãi biển Mỹ Khê lúc bình minh, không một bóng người. Yên bình quá đi",
    "Chạy xe qua đèo Hải Vân, mây trắng bao phủ. Một trong những cung đường đẹp nhất VN",
    "Vịnh Hạ Long nhìn từ trên cao, hàng ngàn hòn đảo nhấp nhô. Kỳ quan thiên nhiên thế giới không phải nói chơi",
    "Chợ Bến Thành Sài Gòn buổi chiều, tấp nập người qua lại. Nhộn nhịp đặc trưng miền Nam 🏙️",
    "Phong Nha Kẻ Bàng, hang động đẹp siêu thực. Ae nào có dịp ghé Quảng Bình nhớ vào đây nha",
];

$shippingPosts = [
    "Ae ơi cho hỏi GHTK giờ tính phí thế nào vậy? Mình thấy phí ship tăng mấy đơn gần đây",
    "J&T bây giờ giao hàng nhanh hơn trước nhiều. Đơn HN-SG 2 ngày là tới luôn ae 👍",
    "Có ae nào đang chạy GHN không? Cho mình hỏi cơ chế thưởng tháng này thế nào",
    "Viettel Post mới ra chính sách mới, ae nào biết chia sẻ với nhé",
    "So sánh phí ship giữa SPX và GHTK, bên nào rẻ hơn ae? Mình đang phân vân chuyển bên",
    "Ninja Van tuyển shipper khu vực Bình Dương, ai biết thông tin liên hệ cho mình với",
    "Ae chạy Grab Express chia sẻ kinh nghiệm với. Thu nhập 1 ngày được bao nhiêu?",
    "Hôm nay giao 50 đơn COD, mệt nhưng vui. Tháng này cố gắng đạt target 🎯",
    "Khách hàng gọi báo hàng bị móp, mà mình giao cẩn thận lắm rồi. Ae xử lý sao trong TH này?",
    "Bên BEST Express có ai đang chạy ko? Nghe nói phí khá cạnh tranh",
    "Tips giao hàng mùa mưa: bọc hàng 2 lớp nilon, để hàng trong thùng xốp, mang áo mưa dự phòng 🌧️",
    "Ae ơi app GHTK bị lỗi không nhận đơn được, có ai bị giống mình ko?",
    "Mới chuyển từ J&T sang GHN, cảm nhận ban đầu là app GHN dễ dùng hơn nhiều",
    "Shipper mới vào nghề cần chú ý gì ae? Em mới bắt đầu chạy được 1 tuần",
    "Hàng dễ vỡ ae giao thế nào cho an toàn? Mình toàn bị khách complain",
];

$confessionPosts = [
    "Confession: Hôm nay giao nhầm đơn, 2 khách nhận nhầm hàng của nhau. May mà 2 người ở gần nhau nên đổi lại được 😅",
    "Thú thật là nghề ship tuy vất vả nhưng mình thấy tự do hơn đi làm văn phòng nhiều",
    "Confession: Có lần giao hàng gặp người yêu cũ, awkward vãi luôn ae 😂",
    "Mưa to quá mà vẫn phải giao, ướt từ đầu đến chân. Khách mở cửa tip thêm 20k, ấm lòng quá 🥺",
    "Nói thật ae, có ngày chạy từ 6h sáng đến 10h đêm mà chỉ được 300k. Vất vả thật sự",
    "Confession: Giao hàng cho 1 chị, chị ấy xinh quá mà mình run tay luôn 😅",
    "Hôm qua bị chó cắn khi giao hàng, may mà chủ nhà đền tiền tiêm phòng",
    "Ae có bao giờ ship đơn xong rồi khách hủy không? Mình bị 3 lần rồi, buồn lắm",
    "Confession: Mình là nữ shipper, nhiều người nhìn ngạc nhiên lắm. Nhưng mà mình chạy không thua gì ae nam đâu nha 💪",
    "Giao hàng lên tầng 20 không thang máy. Lên đến nơi muốn xỉu luôn ae 🥵",
    "Thú nhận là có lần mình ăn thử đồ ăn của khách vì nó thơm quá 🤣 xong phải đền tiền mua lại",
    "Ship 1 năm rồi, giờ thuộc đường Sài Gòn hơn cả Google Maps. Ai hỏi đường gì mình chỉ được hết",
    "Confession: Bị khách chửi oan vì giao chậm do kẹt xe. Về nhà buồn cả đêm",
    "Nghề ship dạy mình nhiều thứ: kiên nhẫn, chịu khó, và biết trân trọng đồng tiền",
    "Có ae nào ship đêm ko? Mình thấy đêm giao ít đơn nhưng đường thoáng, chạy sướng hơn",
];

$discussionPosts = [
    "Ae thấy nghề ship 5 năm nữa sẽ thế nào? Có bị robot thay thế không? 🤖",
    "Theo ae đâu là khu vực khó giao nhất Việt Nam? Mình vote cho nội thành HN giờ cao điểm",
    "Xe máy hay xe tải nhỏ giao hàng hiệu quả hơn? Thảo luận nào ae",
    "Ae có nghĩ nên có công đoàn cho shipper không? Để bảo vệ quyền lợi",
    "Mùa Tết ae tính chạy hay nghỉ? Phí ship Tết cao nhưng cũng vất vả lắm",
    "Chia sẻ playlist nhạc ae hay nghe khi chạy ship với 🎵",
    "Ae dùng điện thoại gì để chạy ship? Pin trâu nhất là dòng nào?",
    "Bảo hiểm cho shipper, ae có mua không? Nên mua loại nào?",
    "Đường Việt Nam ngày càng đông, ae có mẹo gì để tránh kẹt xe ko?",
    "Ae ăn gì khi chạy ship? Chia sẻ quán ăn ngon giá rẻ ven đường nào 🍜",
];

$reviewPosts = [
    "Review găng tay chống nắng mua trên Shopee, dùng 2 tháng rồi vẫn ok. Link để ae tham khảo",
    "Mới mua thùng ship giữ nhiệt, hàng đồ ăn giờ giao tới nơi vẫn nóng hổi. Recommend ae 👍",
    "Review áo mưa bộ 2 lớp, chạy mưa to không thấm. Ae ship mùa mưa nên sắm 1 cái",
    "Điện thoại Redmi Note 13 dùng ship rất ok, pin 5000mAh chạy cả ngày không lo hết pin",
    "Review sạc dự phòng 20000mAh mua cho chạy ship, xài 3 tháng vẫn tốt. Giá 250k thôi ae",
];

$allPosts = [];
foreach ($landscapePosts as $p) $allPosts[] = ['content' => $p, 'type' => 'post'];
foreach ($shippingPosts as $p) $allPosts[] = ['content' => $p, 'type' => 'question'];
foreach ($confessionPosts as $p) $allPosts[] = ['content' => $p, 'type' => 'confession'];
foreach ($discussionPosts as $p) $allPosts[] = ['content' => $p, 'type' => 'discussion'];
foreach ($reviewPosts as $p) $allPosts[] = ['content' => $p, 'type' => 'review'];

// Extra posts to reach 100
$extraPosts = [
    ['content' => 'Sáng nay trời đẹp quá, ae ra đường cẩn thận nha. Chúc ae ngày mới nhiều đơn 💪', 'type' => 'post'],
    ['content' => 'Ae ơi đường Nguyễn Trãi đang kẹt cứng, tránh đi đường khác nha', 'type' => 'tip'],
    ['content' => 'Mới ship xong đơn cuối ngày, về nhà nghỉ ngơi thôi. Good night ae 🌙', 'type' => 'post'],
    ['content' => 'Khách tip 50k vì giao sớm, vui quá ae ơi 🎉', 'type' => 'post'],
    ['content' => 'Có ae nào ở Bình Dương không? Mình mới chuyển về đây, chưa quen đường lắm', 'type' => 'question'],
    ['content' => 'Chia sẻ kinh nghiệm: luôn chụp ảnh hàng trước khi giao để có bằng chứng ae nhé', 'type' => 'tip'],
    ['content' => 'Hôm nay giao 72 đơn, kỷ lục mới của mình! 💪🔥', 'type' => 'post'],
    ['content' => 'Ae nào biết chỗ sửa xe máy uy tín ở quận 7 ko? Xe mình bị hư bugi', 'type' => 'question'],
    ['content' => 'Trời nắng 40 độ mà vẫn phải chạy, uống nước nhiều vào ae 🥤', 'type' => 'post'],
    ['content' => 'Confession: Ship 3 năm rồi, tích được ít vốn mở quán trà sữa nhỏ bên đường 🧋', 'type' => 'confession'],
    ['content' => 'Đường Cầu Giấy sáng nay ngập nặng sau trận mưa đêm qua, ae tránh nha', 'type' => 'tip'],
    ['content' => 'Ae cho hỏi xe Wave Alpha có phù hợp chạy ship không? Mình đang tính mua', 'type' => 'question'],
    ['content' => 'Weekend rồi mà vẫn phải chạy ship. Nhưng mà cuối tuần đơn ít, đường thoáng cũng thích 😌', 'type' => 'post'],
    ['content' => 'Review quán bún bò ngon ở đường Lê Văn Sỹ, 35k/tô mà ngon xuất sắc. Ae ghé thử 🍜', 'type' => 'review'],
    ['content' => 'Ae có dùng app CamScanner để chụp biên nhận không? Mình thấy tiện lắm', 'type' => 'tip'],
    ['content' => 'Mới vào nghề 1 tháng, cảm ơn ae trong group đã giúp đỡ nhiều. Yêu thương ae 🥰', 'type' => 'post'],
    ['content' => 'Đèo Ô Quy Hồ sáng nay sương mù dày, ae chạy tuyến Lào Cai - Lai Châu cẩn thận', 'type' => 'tip'],
    ['content' => 'Có ae nào muốn đổi ca ship khu Thủ Đức không? Mình cần đổi sang ca sáng', 'type' => 'question'],
    ['content' => 'Confession: Giao hàng cho nhà giàu, nhìn biệt thự mà ước mơ. Cố gắng thôi ae 💪', 'type' => 'confession'],
    ['content' => 'Tổng kết tháng: 1500 đơn, thu nhập 18tr. Tháng sau cố gắng hơn nữa 📈', 'type' => 'post'],
];

foreach ($extraPosts as $p) $allPosts[] = $p;
shuffle($allPosts);
$allPosts = array_slice($allPosts, 0, 100);

$postIds = [];
$postCount = 0;

foreach ($allPosts as $idx => $post) {
    $uid = $userIds[array_rand($userIds)];
    $prov = $provinces[array_rand($provinces)];
    $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(0, 72) . ' hours -' . rand(0, 59) . ' minutes'));

    // Some posts have images
    $images = null;
    if ($idx < 15 && count($localImgs) > 0) {
        $imgIdx = $idx % count($localImgs);
        $images = json_encode([$localImgs[$imgIdx]]);
    }

    $isAnon = ($post['type'] === 'confession') ? 1 : 0;

    try {
        // Try simple insert first
        $db->query("INSERT INTO posts (user_id, content, type, `status`, created_at) VALUES (?,?,?,?,?)",
            [$uid, $post['content'], $post['type'], 'active', $createdAt]);
        $pid = $db->getLastInsertId();
        $postIds[] = $pid;
        $postCount++;
        echo "Post $pid (by $uid): " . mb_substr($post['content'], 0, 50) . "...\n";
    } catch (Throwable $e) {
        echo "Skip post: " . $e->getMessage() . "\n";
    }
}

echo "\nCreated $postCount posts\n";

// ================================================
// 4. CREATE COMMENTS (3-8 per post)
// ================================================
echo "\n=== CREATING COMMENTS ===\n";

$commentTemplates = [
    'Đúng quá ae, mình cũng gặp hoàn cảnh tương tự',
    'Cảm ơn ae chia sẻ, hữu ích lắm 👍',
    'Ae nói đúng, mình ủng hộ',
    'Hay quá, follow ae rồi nha',
    'Mình ở {province} cũng vậy luôn ae',
    'Ảnh đẹp quá ae ơi, chụp bằng gì vậy? 📸',
    'Cố lên ae, nghề ship tuy cực nhưng cũng có niềm vui riêng',
    'Mình ship bên {company} thấy ok lắm ae',
    'Cảm ơn ae, note lại rồi',
    'Haha đúng rồi ae 😂',
    'Ae pro quá, mình mới vào nghề cần học hỏi nhiều',
    'Vote cho ae, bài viết chất lượng 🔥',
    'Mình cũng nghĩ vậy ae ạ',
    'Ae có group nào chia sẻ kinh nghiệm không? Add mình với',
    'Trời ơi đẹp quá, mình cũng muốn đi lắm 😍',
    'Ae giao bao lâu rồi? Mình mới 6 tháng thôi',
    'Kinh nghiệm quý giá, cảm ơn ae nhé',
    'Mình ở SG, khu nào cũng kẹt xe hết ae ơi 😅',
    'Ship hàng dễ vỡ phải cẩn thận lắm, mình bị đền 2 lần rồi',
    'Ae cho mình xin thêm thông tin được không?',
    'Like mạnh, bài hay quá',
    'Cùng hãng luôn ae, {company} gang 💪',
    'Mình ghi nhận, cảm ơn ae nhiều',
    'Good luck ae nhé! Chúc ae nhiều đơn 🍀',
    'Real talk đấy ae, ai trong nghề mới hiểu',
    'Save bài này rồi, hữu ích quá',
    'Ae viết hay quá, mong ae chia sẻ thêm nha',
    'Ở {province} có ae nào chạy không? Kết bạn đi',
    'Tháng trước mình cũng gặp chuyện tương tự luôn',
    'Ae chia sẻ thêm kinh nghiệm với mình nhé 🙏',
];

$cmtCount = 0;
foreach ($postIds as $pid) {
    $numCmts = rand(2, 7);
    $usedUsers = [];

    for ($c = 0; $c < $numCmts; $c++) {
        // Pick random user (different from post author and previous commenters)
        $cmtUid = $userIds[array_rand($userIds)];
        $attempts = 0;
        while (in_array($cmtUid, $usedUsers) && $attempts < 10) {
            $cmtUid = $userIds[array_rand($userIds)];
            $attempts++;
        }
        $usedUsers[] = $cmtUid;

        $tpl = $commentTemplates[array_rand($commentTemplates)];
        $tpl = str_replace('{province}', $provinces[array_rand($provinces)], $tpl);
        $tpl = str_replace('{company}', $shippingCompanies[array_rand($shippingCompanies)], $tpl);

        $cmtTime = date('Y-m-d H:i:s', strtotime('-' . rand(0, 48) . ' hours -' . rand(0, 59) . ' minutes'));

        try {
            $db->query("INSERT INTO comments (post_id, user_id, content, `status`, created_at) VALUES (?,?,?,?,?)",
                [$pid, $cmtUid, $tpl, 'active', $cmtTime]);
            $cmtCount++;
        } catch (Throwable $e) {}
    }
}
echo "Created $cmtCount comments\n";

// ================================================
// 5. CREATE LIKES (random)
// ================================================
echo "\n=== CREATING LIKES ===\n";
$likeCount = 0;
foreach ($postIds as $pid) {
    $numLikes = rand(1, 15);
    shuffle($userIds);
    $likers = array_slice($userIds, 0, $numLikes);

    foreach ($likers as $luid) {
        try {
            $db->query("INSERT IGNORE INTO post_likes (post_id, user_id, created_at) VALUES (?,?,NOW())", [$pid, $luid]);
            $likeCount++;
        } catch (Throwable $e) {}
    }
}

// Also add likes table
foreach ($postIds as $pid) {
    $numLikes = rand(0, 8);
    shuffle($userIds);
    $likers = array_slice($userIds, 0, $numLikes);
    foreach ($likers as $luid) {
        try {
            $db->query("INSERT IGNORE INTO likes (post_id, user_id, created_at) VALUES (?,?,NOW())", [$pid, $luid]);
        } catch (Throwable $e) {}
    }
}

echo "Created ~$likeCount likes\n";

// ================================================
// 6. CREATE FOLLOWS (random connections)
// ================================================
echo "\n=== CREATING FOLLOWS ===\n";
$followCount = 0;
foreach ($userIds as $uid) {
    $numFollows = rand(3, 15);
    shuffle($userIds);
    foreach (array_slice($userIds, 0, $numFollows) as $fuid) {
        if ($fuid === $uid) continue;
        try {
            $db->query("INSERT IGNORE INTO follows (follower_id, following_id, created_at) VALUES (?,?,NOW())", [$uid, $fuid]);
            $followCount++;
        } catch (Throwable $e) {}
    }
}
echo "Created ~$followCount follows\n";

echo "\n=============================\n";
echo "SEED COMPLETE!\n";
echo "Users: " . count($userIds) . "\n";
echo "Posts: $postCount\n";
echo "Comments: $cmtCount\n";
echo "Likes: ~$likeCount\n";
echo "Follows: ~$followCount\n";
echo "=============================\n";
