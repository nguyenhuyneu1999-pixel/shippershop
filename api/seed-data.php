<?php
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

$db = db();
echo "=== SHIPPERSHOP SEED DATA ===\n\n";

// Vietnamese names
$hoMale = ['Nguyễn Văn','Trần Đức','Lê Minh','Phạm Hữu','Hoàng Quốc','Vũ Đình','Đặng Công','Bùi Xuân','Đỗ Thanh','Ngô Quang','Dương Hải','Lý Hoàng','Trịnh Bá','Mai Tiến','Hồ Viết','Phan Anh','Lương Thế','Tạ Quốc','Đinh Ngọc','Cao Bá'];
$tenMale = ['Hùng','Dũng','Tuấn','Minh','Đức','Long','Hoàng','Phúc','Thắng','Quang','Hải','Nam','Trung','Tùng','Kiên','Bình','Đạt','Lâm','Khoa','Vũ','Toàn','Nghĩa','Thành','An','Tín'];

$hoFemale = ['Nguyễn Thị','Trần Thị','Lê Thị','Phạm Thị','Hoàng Thị','Vũ Thị','Đặng Thị','Bùi Thị','Đỗ Thị','Ngô Thị','Dương Thị','Lý Thị','Trịnh Thị','Mai Thị','Hồ Thị','Phan Thị','Lương Thị','Tạ Thị','Đinh Thị','Cao Thị'];
$tenFemale = ['Hương','Lan','Mai','Hà','Thảo','Linh','Ngọc','Trang','Thu','Hạnh','Phương','Vy','Chi','Trinh','Yến','Quyên','Giang','Nhi','Anh','Xuân','Thanh','Oanh','Diệu','Tâm','Hiền'];

$companies = ['GHTK','J&T','GHN','Viettel Post','SPX','Ninja Van','BEST','Ahamove','Grab','Be'];
$provinces = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','Bắc Ninh','Hải Dương','Bình Dương','Đồng Nai','Long An','Thanh Hóa','Nghệ An','Thái Nguyên','Lào Cai','Quảng Ninh'];

$bios = [
    'Shipper lâu năm, giao hàng tận tâm 💪','Ship 4 mùa, mưa nắng vẫn đi 🏍️','Giao hàng là đam mê, khách hàng là thượng đế','Chuyên giao hàng nội thành, COD nhanh gọn','Ship đồ ăn + hàng hoá, phục vụ 24/7',
    'Yêu nghề ship, ghét tắc đường 😂','Giao hàng siêu tốc, không lo trễ hẹn','Shipper tự do, nhận đơn mọi lúc','Giao hàng tận nơi, thu tiền tận tay','Ship hàng an toàn, đúng hẹn, uy tín',
    'Đi ship vui lắm, ngày nào cũng có chuyện kể 😆','Chạy ship kiếm thêm ngoài giờ hành chính','Ship pro, 5 năm kinh nghiệm','Giao hàng cẩn thận, không vỡ không nát','Shipper part-time, sinh viên năm 3',
];

// ===== STEP 1: CREATE 100 USERS =====
// Check if seed users already exist
$existingCount = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE id > 10", []);
if (intval($existingCount['c'] ?? 0) >= 100) {
    echo "Step 1: SKIP - 100+ users already exist\n";
    $userIds = [];
    // Jump ahead - dont create users
    } else {
echo "Step 1: Creating 100 users...\n";
$userIds = [];
$avatarDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

for ($i = 0; $i < 100; $i++) {
    $isMale = $i < 50;
    if ($isMale) {
        $fullname = $hoMale[array_rand($hoMale)] . ' ' . $tenMale[array_rand($tenMale)];
    } else {
        $fullname = $hoFemale[array_rand($hoFemale)] . ' ' . $tenFemale[array_rand($tenFemale)];
    }

    // Create username from name
    $username = strtolower(preg_replace('/[^a-z0-9]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fullname))) . rand(10, 99);
    $email = $username . '@gmail.com';
    $phone = '09' . rand(10000000, 99999999);
    $company = $companies[array_rand($companies)];
    $bio = $bios[array_rand($bios)];
    $province = $provinces[array_rand($provinces)];
    $password = password_hash('shipper123', PASSWORD_DEFAULT);
    $daysAgo = rand(1, 90);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

    // Download avatar from UI Avatars (clean, letter-based)
    $colors = ['EE4D2D','1976d2','2e7d32','e65100','6a1b9a','00838f','c62828','4527a0','283593','1565c0','00695c','558b2f','f57f17','e64a19','5d4037'];
    $bgColor = $colors[array_rand($colors)];
    $initials = mb_substr($fullname, 0, 1, 'UTF-8');
    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($fullname) . "&size=200&background={$bgColor}&color=fff&bold=true&format=png";

    $avatarFile = "seed_av_{$i}.png";
    $avatarPath = $avatarDir . $avatarFile;
    $avatarDbPath = "/uploads/avatars/{$avatarFile}";

    // Download avatar
    $imgData = @file_get_contents($avatarUrl);
    if ($imgData) {
        file_put_contents($avatarPath, $imgData);
    }

    try {
        $db->query("INSERT INTO users (fullname,username,email,password,phone,shipping_company,avatar,bio,address,role,`status`,created_at,last_active,is_online) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$fullname, $username, $email, $password, $phone, $company, $avatarDbPath, $bio, $province, 'user', 'active', $createdAt, date('Y-m-d H:i:s', strtotime("-" . rand(0, 48) . " hours")), rand(0, 1)]);
        $uid = $db->getLastInsertId();
        $userIds[] = $uid;
        echo "  User #{$i}: {$fullname} (@{$username}) - {$company} [ID={$uid}]\n";
    } catch (Throwable $e) {
        echo "  SKIP user {$i}: " . $e->getMessage() . "\n";
    }
}

// Re-fetch actual user IDs from DB
$seedUsers = $db->fetchAll("SELECT id FROM users WHERE username LIKE '%seed%' OR email LIKE '%@gmail.com' ORDER BY id DESC LIMIT 100", []);
if (empty($seedUsers)) {
    // Fallback: get latest 100 users (excluding first 10 real users)
    $seedUsers = $db->fetchAll("SELECT id FROM users WHERE id > 10 ORDER BY id DESC LIMIT 100", []);
}
$userIds = array_map(function($u) { return intval($u['id']); }, $seedUsers);
echo "\nFound " . count($userIds) . " user IDs in DB\n\n";
} // end if (users < 100)

// Get user IDs regardless
$seedUsers2 = $db->fetchAll("SELECT id FROM users WHERE id > 10 ORDER BY id ASC LIMIT 100", []);
$userIds = array_map(function($u) { return intval($u['id']); }, $seedUsers2);
echo "Using " . count($userIds) . " user IDs\n\n";

// ===== STEP 2: Download post images from Unsplash (Vietnamese landscapes) =====
echo "Step 2: Downloading post images...\n";
$postImgDir = __DIR__ . '/../uploads/posts/';
if (!is_dir($postImgDir)) mkdir($postImgDir, 0755, true);

$postImages = [];
$unsplashQueries = ['vietnam+landscape','hanoi+vietnam','saigon+vietnam','halong+bay','rice+field+vietnam','vietnam+street','vietnam+motorbike','vietnam+food','danang+vietnam','hue+vietnam'];
for ($q = 0; $q < 10; $q++) {
    for ($p = 0; $p < 3; $p++) {
        $imgId = "seed_post_" . ($q * 3 + $p);
        $imgFile = $imgId . ".jpg";
        $imgPath = $postImgDir . $imgFile;
        // Use picsum for reliable downloads (Unsplash API needs key)
        $w = rand(600, 800);
        $h = rand(400, 600);
        $seed = rand(1, 500);
        $url = "https://picsum.photos/seed/{$seed}/{$w}/{$h}.jpg";
        $data = @file_get_contents($url);
        if ($data && strlen($data) > 1000) {
            file_put_contents($imgPath, $data);
            $postImages[] = "/uploads/posts/{$imgFile}";
            echo "  Image {$imgFile} downloaded\n";
        }
    }
}
echo "Downloaded " . count($postImages) . " images\n\n";

// ===== STEP 3: CREATE 100 POSTS =====
echo "Step 3: Creating 100 posts...\n";

$postContents = [
    // Phong cảnh (type: post)
    ["Hôm nay chạy đơn qua Hồ Tây, trời đẹp quá anh em ơi. Gió mát, nắng vàng, chạy ship mà thấy yêu đời ghê 🌅", "post"],
    ["Chiều nay giao hàng ở Đà Lạt, sương mù phủ kín đường. Cái lạnh se se mà dễ chịu cực kỳ. Ai ở ĐL giao hàng biết cảm giác này ko 🌿", "post"],
    ["Chia sẻ ảnh bình minh ở cầu Rồng Đà Nẵng sáng nay. Chạy ship sớm có cái hay là ngắm được mấy cảnh đẹp như này", "post"],
    ["Hạ Long đẹp vl mọi người ơi, hôm nay chạy đơn ven biển suốt 🌊", "post"],
    ["Giao hàng ở Ninh Bình gặp cảnh đẹp quá phải dừng lại chụp. Tràng An mùa này đẹp lắm nha 📸", "post"],
    ["Sáng nay ship ở Hội An, phố cổ vắng người, yên bình ghê. Chạy chậm thôi chứ ko nỡ chạy nhanh 🏮", "post"],
    ["Phong cảnh Sa Pa đẹp xuất sắc, ruộng bậc thang mùa lúa chín vàng óng. Ai chưa lên Sa Pa thì nên đi 1 lần 🏔️", "post"],
    ["Buổi chiều giao hàng ở quê, đi qua cánh đồng lúa xanh mướt mắt. Mấy lúc như này thấy cuộc sống bình yên ghê", "post"],

    // Hỏi đáp về công ty giao hàng (type: question)
    ["Anh em ơi cho hỏi, GHTK giờ tính phí ship cho shipper thế nào vậy? Có phải trừ 20% ko? Mình mới vào nên chưa rõ lắm 🙏", "question"],
    ["Mn cho mình hỏi J&T với GHN cái nào trả phí ship cho shipper cao hơn ạ? Đang phân vân chuyển sang", "question"],
    ["Viettel Post có ai biết cơ chế thưởng cuối tháng ko ạ? Nghe nói giao trên 500 đơn được bonus?", "question"],
    ["Hỏi xíu: SPX Shopee Express có nhận shipper part-time ko ạ? Mình chỉ chạy được buổi tối thôi", "question"],
    ["Ninja Van khu vực HCM giờ có còn tuyển không mọi người? Lương cứng bao nhiêu vậy?", "question"],
    ["Anh em BEST Express cho mình hỏi: phí COD thu hộ bao nhiêu phần trăm vậy? Có cái nào thấp hơn ko", "question"],
    ["Grab Express so với Ahamove cái nào kiếm được nhiều hơn ạ? Chạy xe máy thôi", "question"],
    ["Ai biết cách đăng ký shipper Lazada ko ạ? Quy trình ntn, cần giấy tờ gì?", "question"],

    // Confession (type: post)
    ["Confession: Hôm nay giao hàng cho crush cũ mà tim đập loạn xạ. Cố tỏ ra bình thường mà tay run run lúc đưa hàng 😅", "post"],
    ["Thú thật là có hôm mệt quá ngồi nghỉ trong công viên ngủ mất 30 phút. Đơn trễ bị khách chửi. Xin lỗi khách 🙇", "post"],
    ["Confession: Làm shipper 3 năm rồi mà gia đình vẫn nói đi tìm việc đàng hoàng. Buồn ghê, shipper cũng là nghề mà 😔", "post"],
    ["Hôm qua ship pizza, khách cho thêm 1 miếng. Vui cả ngày luôn. Có những lúc khách dễ thương vậy đó anh em 🍕", "post"],
    ["Thú nhận là có lần giao hàng bị lạc đường ở khu mới, chạy vòng vòng 40 phút mới tìm ra. Xăng tốn mà đơn ít tiền 😂", "post"],
    ["Confession: Có khách quen hay order, mỗi lần ship xong đều nhắn cảm ơn. Mấy tin nhắn đó làm mình vui cả tuần 💙", "post"],

    // Mẹo hay (type: tip)
    ["Mẹo cho ae: Dùng Google Maps chế độ xe máy, tránh được đường cấm. Tiết kiệm cả tiếng đồng hồ mỗi ngày 👌", "tip"],
    ["Chia sẻ mẹo: Mang theo túi nylon lớn phòng khi trời mưa, bọc hàng lại cho khách. Khách sẽ đánh giá 5 sao ngay", "tip"],
    ["Tips: Charge pin dự phòng luôn mang theo. Hết pin = hết app = hết đơn = hết tiền 🔋", "tip"],
    ["Mẹo tiết kiệm xăng: Tắt máy khi dừng đèn đỏ trên 30 giây. 1 tháng tiết kiệm được kha khá đó ae", "tip"],
    ["Anh em nên mua áo mưa 2 lớp loại tốt. Đầu tư 200k nhưng dùng được cả năm, ko bị ướt hàng", "tip"],

    // Thảo luận (type: post)
    ["Mn nghĩ sao về vụ tăng phí ship gần đây? Khách thì kêu đắt, shipper thì vẫn thu nhập thấp. Tiền đi đâu hết vậy? 🤔", "post"],
    ["Có ai thấy shipper mình cần được bảo hiểm tai nạn ko? Chạy đường cả ngày mà ko có bảo hiểm gì hết", "post"],
    ["Bàn về chuyện shipper bị đánh giá 1 sao oan. Có cách nào khiếu nại hiệu quả ko ae? Bức xúc quá 😤", "post"],
    ["Thời tiết nắng nóng 40 độ mà vẫn phải ship. Các sàn có chính sách gì hỗ trợ shipper mùa nắng ko?", "post"],
    ["Giao hàng ngày lễ Tết có nên tăng phí ship không? Mình thấy Grab tăng nhưng mấy hãng khác thì không", "post"],
    ["Ae ơi có ai gặp tình trạng khách đặt COD rồi bom hàng ko? Cách xử lý ntn cho hiệu quả?", "post"],
    ["Nói thật là ship thời gian này khó hơn 2-3 năm trước nhiều. Cạnh tranh cao, phí thấp, đơn ít. Ae có thấy vậy ko?", "post"],

    // Chia sẻ thường ngày
    ["Đi ship gặp bác xe ôm già, bác kể ngày xưa chạy xe ôm nuôi 3 đứa con ăn học. Giờ con thành đạt hết. Cảm động quá 🥺", "post"],
    ["Hôm nay đạt 50 đơn rồi ae ơi! Target tháng này chắc được thưởng. Cố lên nào 💪🔥", "post"],
    ["Mới mua được cái ba lô giao hàng mới, giữ nhiệt tốt lắm. Giao đồ ăn giữ nóng nguyên, khách khen hoài 🎒", "post"],
    ["Trời mưa to quá mà vẫn phải chạy. Ae nhớ cẩn thận đường trơn nhé. An toàn là trên hết 🌧️", "post"],
    ["Cuối tháng rồi, tổng kết: 800 đơn, thu nhập 12tr. Tháng sau cố gắng hơn nữa 📊", "post"],
    ["Giao hàng cho 1 bà cụ, bà nói con ship ơi uống ly nước rồi đi. Ngồi nói chuyện với bà 10 phút. Bà sống 1 mình, thấy thương 😢", "post"],
    ["Ship được 1 tháng rồi. Kinh nghiệm rút ra: kiên nhẫn, lịch sự, và luôn kiểm tra hàng trước khi giao 👍", "post"],
    ["Hôm nay chạy qua trường cũ, nhớ thời sinh viên ghê. Giờ chạy ship lo cơm áo nhưng vẫn vui 😊", "post"],
    ["Ae nào chạy khu vực Bình Dương cho mình hỏi đường nào hay kẹt xe nhất để tránh?", "question"],
    ["Sáng ra trời đẹp, gió mát, lên xe chạy đơn thôi nào. Chúc ae ngày mới nhiều đơn 🏍️☀️", "post"],
    ["Ship hàng cho tiệm bánh, chị chủ tặng 2 cái bánh mì nóng giòn. Ăn trên đường ship ngon đến quên đường về 😋", "post"],
    ["Có ai biết app nào check lộ trình giao hàng tối ưu ko? Mình giao nhiều điểm 1 lúc mà ko biết sắp xếp 🗺️", "question"],
    ["Tháng này được khách tip 3 lần rồi. Vui quá trời. Giao hàng mà cười cả ngày 😄💰", "post"],
    ["Chia sẻ: Khi giao hàng cho khách, luôn gọi trước 5 phút. Khách chuẩn bị sẵn = giao nhanh = nhiều đơn hơn 📞", "tip"],
    ["Ước gì có thêm nhiều trạm nghỉ cho shipper. Đi cả ngày mà ko có chỗ ngồi nghỉ tử tế 😓", "post"],
    ["Đêm nay ai còn chạy đơn ko? Đường vắng, gió mát, ship đêm cũng vui phết 🌙", "post"],
    ["Mới bị phạt vì đỗ xe sai chỗ lúc giao hàng. Mất 300k. Ae cẩn thận nhé, đỗ đúng nơi quy định 🚫", "post"],
    ["Review: Áo khoác chống nắng UPF50 trên Shopee, mua 150k. Dùng 2 tháng rồi, mát thật sự, xứng đáng mua", "review"],
    ["Có bao giờ ae ship mà gặp đồng nghiệp cùng giao 1 chỗ ko? Hôm nay gặp 3 shipper khác cùng tòa, vui ghê 😂", "post"],
    ["Nghỉ 1 ngày ko chạy ship mà thấy nhớ. Nghiện rồi hay sao ấy 🤣", "post"],
    ["Khoe: Hôm nay được khách tặng lon nước ngọt. Trời nóng 38 độ mà có lon nước mát lạnh, sướng như tiên 🥤", "post"],
];

$commentContents = [
    'Chuẩn luôn ae ơi 👍','Đúng vậy, mình cũng gặp rồi','Like mạnh!','Cảm ơn chia sẻ nha','Hay quá, note lại 📝',
    'Mình cũng nghĩ vậy','Chia sẻ hữu ích quá','Bài hay, follow luôn','Cảm ơn ae nhiều nha','Kinh nghiệm thực tế 👏',
    'Đồng ý 100%','Mình ship GHTK, confirm luôn','À thì ra vậy, cảm ơn nha','Ae pro quá','Ghi nhớ 💡',
    'Ship bao lâu rồi ae?','Cùng cảnh ngộ luôn 😅','Khu mình cũng vậy nè','Mình thử rồi, hiệu quả thật','Ae giỏi quá',
    'Ước gì mình cũng được vậy','Thiệt hả? Để thử coi','Cảm ơn info ae 🙏','Hay lắm, share cho anh em','Mong sớm cải thiện',
    'Ai ở HCM cho mình hỏi thêm','Khu Hà Nội có khác ko ae?','Mình mới vào nghề, học hỏi nhiều','Bữa mình cũng bị y chang 😤',
    'Chia sẻ thêm đi ae','Ae nói đúng quá','Haha đúng rồi 😂','Ship vui mà ae','Cố lên nào 💪','An toàn nhé ae 🤞',
    'Thông tin bổ ích ghê','Lần đầu biết luôn á','Thanks ae','Có link ko ae?','Mình ghi nhớ rồi nè',
];

$postIds = [];
shuffle($postContents);
$numPosts = min(100, count($postContents));

for ($i = 0; $i < $numPosts; $i++) {
    $pc = $postContents[$i];
    $content = $pc[0];
    $type = $pc[1];
    $uid = $userIds[array_rand($userIds)];
    $province = $provinces[array_rand($provinces)];
    $daysAgo = rand(0, 30);
    $hoursAgo = rand(0, 23);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));

    // Random images (30% chance of having image)
    $images = '[]';
    if (rand(1, 100) <= 30 && count($postImages) > 0) {
        $numImgs = rand(1, min(3, count($postImages)));
        $selectedImgs = array_rand(array_flip($postImages), $numImgs);
        if (!is_array($selectedImgs)) $selectedImgs = [$selectedImgs];
        $images = json_encode(array_values($selectedImgs));
    }

    try {
        $db->query("INSERT INTO posts (user_id,content,images,type,province,`status`,created_at,likes_count,comments_count,views) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$uid, $content, $images, $type, $province, 'active', $createdAt, rand(0, 50), 0, rand(10, 500)]);
        $pid = $db->getLastInsertId();
        $postIds[] = $pid;
        echo "  Post #{$i}: [ID={$pid}] " . mb_substr($content, 0, 50) . "...\n";
    } catch (Throwable $e) {
        echo "  SKIP post {$i}: " . $e->getMessage() . " SQL user_id={$uid} type={$type}\n";
    }
}

echo "\nCreated " . count($postIds) . " posts\n\n";

// ===== STEP 4: ADD COMMENTS =====
echo "Step 4: Adding comments...\n";
$commentCount = 0;
foreach ($postIds as $pid) {
    $numComments = rand(1, 6);
    for ($c = 0; $c < $numComments; $c++) {
        $uid = $userIds[array_rand($userIds)];
        $cContent = $commentContents[array_rand($commentContents)];
        $daysAgo = rand(0, 15);
        $hoursAgo = rand(0, 23);
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));

        try {
            $db->query("INSERT INTO comments (post_id,user_id,content,`status`,created_at,likes_count) VALUES (?,?,?,?,?,?)",
                [$pid, $uid, $cContent, 'active', $createdAt, rand(0, 10)]);
            $commentCount++;
        } catch (Throwable $e) {}
    }
    // Update comments_count
    try {
        $cnt = $db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id=? AND `status`='active'", [$pid]);
        $db->query("UPDATE posts SET comments_count=? WHERE id=?", [$cnt['c'] ?? 0, $pid]);
    } catch (Throwable $e) {}
}
echo "Created {$commentCount} comments\n\n";

// ===== STEP 5: ADD LIKES =====
echo "Step 5: Adding likes...\n";
$likeCount = 0;
foreach ($postIds as $pid) {
    $numLikes = rand(1, 20);
    $likers = array_rand(array_flip($userIds), min($numLikes, count($userIds)));
    if (!is_array($likers)) $likers = [$likers];
    foreach ($likers as $likerId) {
        try {
            $db->query("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,NOW())", [$pid, $likerId]);
            $likeCount++;
        } catch (Throwable $e) {}
    }
    // Update likes_count
    try {
        $cnt = $db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?", [$pid]);
        $db->query("UPDATE posts SET likes_count=? WHERE id=?", [$cnt['c'] ?? 0, $pid]);
    } catch (Throwable $e) {}
}
echo "Created {$likeCount} likes\n\n";

// ===== STEP 6: ADD FOLLOWS =====
echo "Step 6: Adding follows...\n";
$followCount = 0;
foreach ($userIds as $uid) {
    $numFollows = rand(3, 15);
    $targets = array_rand(array_flip($userIds), min($numFollows, count($userIds)));
    if (!is_array($targets)) $targets = [$targets];
    foreach ($targets as $target) {
        if ($target == $uid) continue;
        try {
            $db->query("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())", [$uid, $target]);
            $followCount++;
        } catch (Throwable $e) {}
    }
}
echo "Created {$followCount} follows\n\n";

// Cleanup
// rm(__FILE__);
echo "=== DONE! ===\n";
echo "Users: " . count($userIds) . "\n";
echo "Posts: " . count($postIds) . "\n";
echo "Comments: {$commentCount}\n";
echo "Likes: {$likeCount}\n";
echo "Follows: {$followCount}\n";

function rm($f) { @unlink($f); }
