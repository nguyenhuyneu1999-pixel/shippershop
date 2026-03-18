<?php
set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db = db();

// 1. Remove ALL seed images from posts (they're random and don't match content)
$db->query("UPDATE posts SET images='[]' WHERE images LIKE '%seed_%'", []);
echo "Cleared mismatched seed images\n";

// 2. Delete ALL old seed posts (we'll create better ones)
$db->query("DELETE FROM comments WHERE post_id IN (SELECT id FROM posts WHERE user_id > 10 AND id > 130)", []);
$db->query("DELETE FROM post_likes WHERE post_id IN (SELECT id FROM posts WHERE user_id > 10 AND id > 130)", []);
$db->query("DELETE FROM posts WHERE user_id > 10 AND id > 130", []);
echo "Deleted old seed posts\n";

// 3. Get user IDs
$users = $db->fetchAll("SELECT id, fullname, shipping_company FROM users WHERE id > 10 AND `status`='active' ORDER BY RAND() LIMIT 100", []);
$userIds = array_map(function($u){return intval($u['id']);}, $users);
echo "Users: " . count($userIds) . "\n";

// 4. Create 100 high-quality posts - TEXT ONLY (realistic for shipper community)
// Content matched to Vietnamese shipper culture
$posts = [
    // === CHIA SẺ ĐƯỜNG ĐI (20 bài) ===
    ["Sáng nay chạy đơn ở khu Cầu Giấy, đường Xuân Thủy kẹt cứng từ 7h. Ae tránh đi đường Phạm Văn Đồng nhé, thông hơn nhiều 🏍️", "post", "Hà Nội"],
    ["Đường Nguyễn Xiển chiều nay ngập nặng sau cơn mưa. Ae ship khu vực Thanh Xuân nên đi vòng Khuất Duy Tiến", "post", "Hà Nội"],
    ["Ship ở Thủ Đức hôm nay gặp chốt CSGT ở ngã tư Bình Thái. Ae nhớ mang đủ giấy tờ nha 🚔", "post", "TP. Hồ Chí Minh"],
    ["Khu vực Q7 PMH đường Nguyễn Lương Bằng đang đào ống nước, kẹt kinh hoàng. Đi đường Nguyễn Hữu Thọ thay nhé ae", "post", "TP. Hồ Chí Minh"],
    ["Cầu Rồng Đà Nẵng cuối tuần phun lửa đông nghẹt, ae ship khu vực Sơn Trà đi cầu Thuận Phước nha", "post", "Đà Nẵng"],
    ["Đường Lê Hồng Phong Hải Phòng đang sửa, bụi mù mịt. Ae đeo khẩu trang kín nhé, đường này ship nhiều đơn lắm", "post", "Hải Phòng"],
    ["Khu công nghiệp Bình Dương chiều tan ca xe tải chạy dày đặc. Ae cẩn thận, đi sát lề phải", "post", "Bình Dương"],
    ["Hôm nay ship ở Long Biên, qua cầu Vĩnh Tuy lúc 6h chiều đông kinh khủng. Nên đi cầu Chương Dương", "post", "Hà Nội"],
    ["Ngã tư Hàng Xanh lúc nào cũng kẹt. Mẹo: đi đường Điện Biên Phủ rẽ vào Bạch Đằng, thoát kẹt ngay", "post", "TP. Hồ Chí Minh"],
    ["Đường lên Sapa đoạn đèo Ô Quy Hồ hôm nay sương mù dày. Ae ship khu vực này bật đèn sáng nhé ⚠️", "post", "Lào Cai"],
    ["Khu đô thị Ecopark đường vào nhiều vòng xoay, lần đầu ship vào đây lạc 3 vòng 😂", "post", "Hà Nội"],
    ["Quốc lộ 1A đoạn qua Ninh Bình đang mở rộng, đường xấu + bụi. Ae chạy chậm thôi", "post", "Thanh Hóa"],
    ["Ship ở khu Phú Mỹ Hưng toàn villa, cổng bảo vệ hỏi giấy. Nên gọi khách ra cổng nhận cho nhanh", "post", "TP. Hồ Chí Minh"],
    ["Đường vào KCN Amata Đồng Nai giờ cao điểm xe container chạy nhiều. Ship xe máy cẩn thận ae 🚛", "post", "Đồng Nai"],
    ["Khu Ciputra Tây Hồ bảo vệ kiểm tra nghiêm lắm. Mang CMND + đơn hàng mới cho vào ae nhé", "post", "Hà Nội"],

    // === HỎI ĐÁP VỀ CÔNG TY GIAO HÀNG (20 bài) ===
    ["Ae ơi cho hỏi GHTK giờ có chính sách gì mới cho shipper không? Mình nghe nói tăng phí từ tháng này?", "question", "Hà Nội"],
    ["J&T so với GHN cái nào trả phí ship cao hơn ạ? Mình đang chạy J&T 8 tháng rồi, phân vân muốn đổi", "question", "TP. Hồ Chí Minh"],
    ["Viettel Post có còn tuyển shipper ở khu vực Đà Nẵng không ạ? Yêu cầu gì? Xe SH có đc ko", "question", "Đà Nẵng"],
    ["SPX Shopee Express có nhận part-time không mn? Mình sinh viên chỉ chạy được 4h/ngày", "question", "Hà Nội"],
    ["Ninja Van khu vực Bình Dương lương cứng bao nhiêu vậy ae? Có phải đóng tiền cọc ko?", "question", "Bình Dương"],
    ["BEST Express phí COD bao nhiêu phần trăm ae? So với GHTK thì thấp hơn hay cao hơn", "question", "TP. Hồ Chí Minh"],
    ["Ahamove với Grab Express cái nào kiếm nhiều hơn ae? Chạy xe máy 110cc", "question", "Hà Nội"],
    ["Đăng ký shipper Lazada cần giấy tờ gì ae? Có phải mua đồng phục không?", "question", "TP. Hồ Chí Minh"],
    ["Be Express có ai chạy chưa? Nghe nói mới mở tuyển, phí ship về tay cao hơn Grab?", "question", "Hà Nội"],
    ["GHTK bảo hiểm cho shipper như nào ae? Hôm trước bị tai nạn ko biết có được hỗ trợ ko", "question", "Nghệ An"],
    ["Ae nào biết cách xin nghỉ phép ở GHN ko? Mình cần nghỉ 3 ngày mà sợ bị trừ KPI", "question", "TP. Hồ Chí Minh"],
    ["Tiki Now có tuyển shipper tự do ko ae? Hay bắt buộc phải full-time?", "question", "Hà Nội"],
    ["J&T có chương trình thưởng cuối năm cho shipper ko ae? Năm ngoái có ai nhận chưa?", "question", "TP. Hồ Chí Minh"],
    ["Ae chạy Viettel Post cho hỏi: đơn COD trên 5 triệu có phải gọi xác nhận ko?", "question", "Thanh Hóa"],
    ["Gojek so với Grab, chạy đồ ăn cái nào nhiều đơn hơn ae? Khu vực Hà Nội", "question", "Hà Nội"],

    // === CONFESSION (15 bài) ===
    ["Confession: Hôm nay giao hàng cho crush cũ, cầm đơn mà tay run. Crush nhận hàng nói cảm ơn anh ship, thế là xong 😅💔", "post", "Hà Nội"],
    ["Thú thật có hôm mệt quá ngồi ven đường nghỉ, ngủ gật 15 phút. Tỉnh dậy thấy có bác bán nước cho 1 ly trà đá free. Cảm ơn bác 🙏", "post", "TP. Hồ Chí Minh"],
    ["Confession: 3 năm làm shipper nuôi mộng cưới vợ. Hôm nay nộp tiền đặt cọc nhà rồi ae ơi. Cảm ơn nghề ship 🏠❤️", "post", "Bình Dương"],
    ["Hôm qua ship pizza bị mưa, dừng xe che hàng cho khách. Pizza nguyên vẹn mà mình ướt như chuột. Khách thấy thương tặng thêm 50k 🥹", "post", "Đà Nẵng"],
    ["Thú nhận: Có lần ship sai địa chỉ, chạy thêm 10km quay lại. Tốn xăng nhưng ko dám nói ai, sợ bị đánh giá thấp 😓", "post", "Hà Nội"],
    ["Confession: Khách quen mỗi lần ship đều nhắn cảm ơn em, kèm tip 10k. Mấy tin nhắn đó làm mình vui cả tuần luôn 💙", "post", "TP. Hồ Chí Minh"],
    ["Thú thật: Hôm nay ship cho bệnh viện, thấy mấy bệnh nhân nằm chờ, thấy mình may mắn vì còn khỏe để chạy xe 🥺", "post", "Hà Nội"],
    ["Confession: Làm shipper mới biết thành phố rộng thế nào. 2 năm chạy mà vẫn lạc đường khu mới 😂", "post", "TP. Hồ Chí Minh"],
    ["Confession: Có khách gọi sai tên mình suốt 6 tháng, mình ko dám sửa vì sợ mất khách quen 😅", "post", "Đà Nẵng"],
    ["Thú thật mỗi lần giao hàng cho quán ăn, mùi thức ăn thơm mà bụng đói cồn cào. Nhưng phải cố giao xong mới được ăn 😤🍜", "post", "Hà Nội"],
    ["Confession: Ship 1 năm rồi mà vẫn sợ chó. Mỗi lần vào ngõ có chó sủa là tim đập loạn 🐕😱", "post", "Thanh Hóa"],
    ["Hôm nay là sinh nhật mà vẫn chạy ship. Khách cuối ngày tặng cái bánh, hóa ra khách nhớ sinh nhật mình từ lần trước giao hàng 🎂", "post", "Hà Nội"],
    ["Confession: Giao đồ ăn cho đám cưới, thấy cô dâu chú rể hạnh phúc quá. Chạy ra ngoài ngồi 5 phút nhớ người yêu cũ 😢", "post", "TP. Hồ Chí Minh"],
    ["Ship hàng cho 1 bà cụ sống 1 mình, bà bảo: Con ơi ở lại nói chuyện với bà chút đi. Ngồi 15 phút nghe bà kể chuyện 🥺", "post", "Nghệ An"],
    ["Confession: Bố mẹ bảo sao ko đi làm công ty cho ổn định. Mình nói con thích tự do. Thật ra là mình thích ngắm phố phường 🏙️", "post", "Hà Nội"],

    // === MẸO HAY (10 bài) ===
    ["Mẹo cho ae: Dùng Google Maps chế độ xe máy, tránh được đường cấm + tìm đường tắt. Mình tiết kiệm 1-2 tiếng mỗi ngày 🗺️", "tip", "Hà Nội"],
    ["Tips: Bọc hàng dễ vỡ bằng 2 lớp túi bóng khí + giấy báo bên ngoài. Ship 500 đơn chưa vỡ cái nào 📦", "tip", "TP. Hồ Chí Minh"],
    ["Mẹo tiết kiệm xăng: Tắt máy khi dừng đèn đỏ trên 30 giây + bơm lốp đủ hơi. Tiết kiệm 200k/tháng", "tip", "Hà Nội"],
    ["Tips: Mang theo pin sạc dự phòng 20000mAh. Hết pin = hết app = hết đơn = hết tiền 🔋", "tip", "TP. Hồ Chí Minh"],
    ["Ae nên mua áo mưa 2 lớp loại tốt (200-300k). Dùng cả năm, ko bị ướt hàng, ko bị ốm", "tip", "Đà Nẵng"],
    ["Mẹo: Gọi khách trước 5 phút khi sắp đến. Khách chuẩn bị sẵn tiền/ra cổng = giao nhanh = nhiều đơn hơn 📞", "tip", "Hà Nội"],
    ["Tips ship đồ ăn: Để hộp nước ngang, hộp cơm đứng. Nước ko tràn + cơm ko bị nát. Khách đánh giá 5 sao 🍱", "tip", "TP. Hồ Chí Minh"],
    ["Mẹo chụp ảnh xác nhận giao hàng: Chụp kèm số nhà + mặt khách (nếu cho phép). Tránh bị claim ko nhận hàng 📸", "tip", "Bình Dương"],
    ["Tips mùa mưa: Bọc điện thoại trong túi zip-lock, dán trên giá đỡ. Dùng GPS bình thường mà ko sợ ướt 📱💧", "tip", "Hà Nội"],
    ["Mẹo: Sắp xếp đơn theo khu vực trước khi đi. Giao xong 1 cụm mới sang cụm tiếp. Tiết kiệm 30% quãng đường 🧠", "tip", "TP. Hồ Chí Minh"],

    // === THẢO LUẬN (15 bài) ===
    ["Mn nghĩ sao về vụ tăng phí ship gần đây? Khách kêu đắt, mình thì vẫn lương thấp. Tiền đi đâu hết? 🤔", "post", "Hà Nội"],
    ["Shipper mình cần được bảo hiểm tai nạn ko ae? Chạy đường cả ngày mà ko có bảo hiểm gì", "post", "TP. Hồ Chí Minh"],
    ["Bàn về chuyện shipper bị đánh giá 1 sao oan. Có cách nào khiếu nại hiệu quả ko ae?", "post", "Đà Nẵng"],
    ["Trời nắng 40 độ vẫn phải ship. Các sàn có chính sách hỗ trợ shipper mùa nắng ko?", "post", "TP. Hồ Chí Minh"],
    ["Ae có gặp tình trạng khách đặt COD rồi bom hàng ko? Xử lý thế nào cho hiệu quả?", "post", "Hà Nội"],
    ["Ship thời gian này khó hơn 2-3 năm trước nhiều. Cạnh tranh cao, phí thấp, đơn ít. Ae có thấy vậy ko?", "post", "Bình Dương"],
    ["Nên nghỉ ship chuyển nghề hay tiếp tục? Thu nhập 8-10tr/tháng nhưng hao mòn sức khỏe + xe 🤔", "post", "Hà Nội"],
    ["Ae thấy shipper có nên lập công đoàn/hiệp hội ko? Để bảo vệ quyền lợi chung", "post", "TP. Hồ Chí Minh"],
    ["Bàn về xe điện cho shipper: VinFast Feliz giá 30tr, đủ chạy 1 ngày ko? Ai dùng rồi review đi", "post", "Hà Nội"],
    ["Phí xăng tăng liên tục mà phí ship ko tăng. Ae tính lại thu nhập thực tế bao nhiêu/đơn?", "post", "TP. Hồ Chí Minh"],
    ["Khách hàng nên tip shipper bao nhiêu là hợp lý? Ae nghĩ sao về văn hóa tip ở VN?", "post", "Đà Nẵng"],
    ["Ship đêm vs ship ngày: cái nào kiếm nhiều hơn? Mình thấy ship đêm ít kẹt xe nhưng nguy hiểm hơn", "post", "Hà Nội"],
    ["Ae có lưu lại danh sách khách quen ko? Mình có ~50 khách hay order, họ tip đều luôn", "post", "TP. Hồ Chí Minh"],
    ["Bàn về app giao hàng nào dễ dùng nhất? GHTK, GHN hay J&T? Giao diện + tính năng", "post", "Thanh Hóa"],
    ["Cuối năm rồi ae, ship Tết có nên tăng phí ko? Năm ngoái mình ship Tết kiếm gấp 3 ngày thường", "post", "Hà Nội"],

    // === REVIEW (5 bài) ===
    ["Review áo khoác chống nắng UPF50 trên Shopee 150k. Dùng 2 tháng, mát thật sự, xứng đáng mua cho ae ship ☀️", "review", "TP. Hồ Chí Minh"],
    ["Review túi giữ nhiệt cho shipper đồ ăn: Loại 80k trên Lazada, giữ nóng 1 tiếng, đủ dùng. Loại 200k giữ 3 tiếng. Tùy ae chọn", "review", "Hà Nội"],
    ["Review sạc dự phòng Xiaomi 20000mAh cho shipper. Giá 350k, sạc nhanh 18W, chạy nguyên ngày ko hết. Đáng mua 🔋", "review", "Đà Nẵng"],
    ["Review bao tay chống nắng Lazada 45k. Vải mỏng nhẹ, chống UV tốt, ship mùa hè cần thiết. 8/10 🧤", "review", "TP. Hồ Chí Minh"],
    ["Review kính râm phân cực cho shipper 120k. Chống chói nắng tốt, đeo helmet vẫn vừa. Ae nên sắm 1 cái 🕶️", "review", "Hà Nội"],

    // === CHIA SẺ THƯỜNG NGÀY (15 bài) ===
    ["Hôm nay đạt 50 đơn rồi ae! Target tháng này chắc được thưởng. Cố lên nào 💪🔥", "post", "Hà Nội"],
    ["Trời mưa to quá mà vẫn chạy. Ae nhớ cẩn thận đường trơn nhé. An toàn là trên hết 🌧️", "post", "TP. Hồ Chí Minh"],
    ["Cuối tháng rồi, tổng kết: 800 đơn, thu nhập 12tr. Tháng sau cố gắng hơn 📊", "post", "Hà Nội"],
    ["Giao hàng cho bà cụ, bà mời vào uống nước. Ngồi 10 phút nghe bà kể chuyện. Thấy thương 🥺", "post", "Thanh Hóa"],
    ["Ship hàng cho tiệm bánh, chị chủ tặng 2 cái bánh mì nóng giòn. Ăn trên đường ngon quên lối về 😋🍞", "post", "TP. Hồ Chí Minh"],
    ["Hôm nay có khách nhắn: Cảm ơn anh ship giao nhanh quá! 1 tin nhắn nhỏ mà vui cả ngày 💙", "post", "Đà Nẵng"],
    ["Tháng này được khách tip 5 lần. Giao hàng mà cười cả ngày 😄💰", "post", "Hà Nội"],
    ["Mới bị phạt vì đỗ xe sai chỗ lúc giao hàng. Mất 300k. Ae cẩn thận nhé, đỗ đúng nơi 🚫", "post", "TP. Hồ Chí Minh"],
    ["Nghỉ 1 ngày ko ship mà thấy nhớ. Nghiện rồi hay sao ấy 🤣", "post", "Bình Dương"],
    ["Ship đồ ăn 3km mà khách tip 50k. Trời ơi dễ thương quá! Gặp khách này mỗi ngày thì giàu 😄", "post", "Hà Nội"],
    ["3 năm làm shipper, điều mình học được: Kiên nhẫn, lễ phép, luôn cười. Khách cảm nhận được thái độ mình ❤️", "post", "TP. Hồ Chí Minh"],
    ["Sáng nay giao đơn đầu tiên lúc 6h. Phố vắng, gió mát, chạy xe thấy thích. Best time to ship 🌤️", "post", "Hà Nội"],
    ["Tổng kết tuần: 280 đơn, thu nhập 4.2tr. Keep going ae 📈💰", "post", "Đà Nẵng"],
    ["Hôm nay ship cho 1 em bé. Em hỏi: Anh ship ơi sao đi nhiều thế? Trả lời: Vì anh muốn mang niềm vui đến mọi người 😊", "post", "Hà Nội"],
    ["Ae ơi có ai gặp bug app GHN hôm nay ko? Đơn báo giao rồi nhưng app hiện đang giao. Loạn 🐛", "question", "TP. Hồ Chí Minh"],
];

$commentPool = [
    'Chuẩn ae 👍','Đúng r, mình cũng gặp','Like mạnh!','Cảm ơn ae','Hay quá 📝',
    'Mình cũng nghĩ vậy','Chia sẻ hữu ích','Follow luôn','Cảm ơn ae nhiều','Kinh nghiệm thực tế 👏',
    'Đồng ý 100%','Mình ship GHTK, xác nhận','À ra vậy, thanks','Ae pro quá','Ghi nhớ 💡',
    'Ship bao lâu rồi ae?','Cùng cảnh ngộ 😅','Khu mình cũng thế','Mình thử rồi, ok','Ae giỏi quá',
    'Thiệt hả? Để thử','Info hay ae 🙏','Hay lắm, share tiếp đi','Mong cải thiện sớm',
    'HCM cũng vậy ae','Khu Hà Nội khác ko ae?','Mới vào nghề, đang học hỏi','Bữa mình cũng bị 😤',
    'Ae nói chuẩn ko chỉnh','Haha đúng r 😂','Ship vui mà ae','Cố lên ae 💪','An toàn nhé 🤞',
    'Lần đầu biết luôn','Thanks ae!','Real talk','Đọc mà thấy đồng cảm','Ae viết hay ghê',
    'Mình ship J&T, cũng thế','Ae chạy xe gì?','Xăng bao nhiêu 1 ngày?','Thu nhập bình quân bnh ae?',
    'Chia sẻ thêm đi ae','Có ai khu Bình Dương ko?','Mình ở Đà Nẵng, confirm','Respect ae 🫡',
    'Ae cho hỏi thêm','Mình note lại rồi','Bài hay, save luôn','Ae kinh nghiệm dày ghê',
];

$postIds = [];
echo "\n=== Creating 100 posts ===\n";
for ($i = 0; $i < count($posts); $i++) {
    $p = $posts[$i];
    $uid = $userIds[array_rand($userIds)];
    $daysAgo = rand(0, 14);
    $hoursAgo = rand(1, 23);
    $minsAgo = rand(0, 59);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours -{$minsAgo} minutes"));

    try {
        $db->query("INSERT INTO posts (user_id,content,images,type,province,`status`,created_at,likes_count,comments_count,views) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$uid, $p[0], '[]', $p[1], $p[2], 'active', $createdAt, 0, 0, rand(30, 800)]);
        $pid = intval($db->fetchOne("SELECT MAX(id) as m FROM posts", [])['m'] ?? 0);
        $postIds[] = $pid;

        // Add likes
        $numLikes = rand(5, 35);
        $likers = array_slice($userIds, 0, min($numLikes, count($userIds)));
        shuffle($likers);
        foreach (array_slice($likers, 0, $numLikes) as $lk) {
            try { $db->query("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,?)", [$pid, $lk, $createdAt]); } catch (Throwable $e) {}
        }
        $lc = intval($db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?", [$pid])['c'] ?? 0);

        // Add comments
        $numCmts = rand(3, 10);
        for ($c = 0; $c < $numCmts; $c++) {
            $cu = $userIds[array_rand($userIds)];
            $cc = $commentPool[array_rand($commentPool)];
            $ct = date('Y-m-d H:i:s', strtotime($createdAt . " + " . rand(10, 2880) . " minutes"));
            try { $db->query("INSERT INTO comments (post_id,user_id,content,`status`,created_at,likes_count) VALUES (?,?,?,?,?,?)", [$pid, $cu, $cc, 'active', $ct, rand(0, 8)]); } catch (Throwable $e) {}
        }
        $cc2 = intval($db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id=? AND `status`='active'", [$pid])['c'] ?? 0);
        $db->query("UPDATE posts SET likes_count=?, comments_count=? WHERE id=?", [$lc, $cc2, $pid]);

        echo "  #{$i} [ID={$pid}] 👍{$lc} 💬{$cc2}: " . mb_substr($p[0], 0, 45, 'UTF-8') . "...\n";
    } catch (Throwable $e) {
        echo "  SKIP #{$i}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DONE ===\n";
$total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
echo "Total active posts: " . $total['c'] . "\n";
echo "Created: " . count($postIds) . " posts\n";
