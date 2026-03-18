<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain');

// Get existing user IDs (skip id=1)
$users = $pdo->query("SELECT id FROM users WHERE id > 1 AND status='active' ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
$groupIds = [1,2,3,4,5,6,7,8,9,10,11,12];

$posts = [
// Group 1: Shipper GHTK (25 posts)
[1,"Hôm nay giao 45 đơn khu vực Quận 7, toàn hẻm nhỏ mà app GHTK chỉ đường vòng vèo. Có ai biết cách tối ưu tuyến đường trên GHTK không?"],
[1,"Mẹo: Khi nhận đơn COD trên 2 triệu, nên gọi khách trước 30 phút. Tỷ lệ giao thành công tăng từ 70% lên 95%. Mình áp dụng 3 tháng rồi, rất hiệu quả!"],
[1,"Có ai gặp lỗi app GHTK bị treo khi scan mã vạch không? Từ sáng tới giờ scan hoài không được, phải nhập tay mệt quá."],
[1,"Chia sẻ kinh nghiệm: Đơn hoàn nên xử lý trong ngày, để qua ngày bị trừ KPI. Mình bị 1 lần rồi, mất 50k tiền phạt."],
[1,"GHTK vừa update bảng giá mới, đơn nội tỉnh tăng 2k. Anh em có thấy thu nhập bị ảnh hưởng không?"],
[1,"Hỏi: Đơn giao 3 lần không thành công thì GHTK xử lý thế nào? Có bị trừ tiền shipper không?"],
[1,"Tips giao hàng mùa mưa: Luôn mang theo túi nilong lớn bọc hàng. Khách nhận hàng khô ráo sẽ đánh giá 5 sao."],
[1,"Vừa được thưởng shipper xuất sắc tháng 3! Giao 1200 đơn, tỷ lệ thành công 98.5%. Chia sẻ bí quyết: luôn gọi khách trước 1 tiếng."],
[1,"Cảnh báo: Khu vực Bình Tân có mấy địa chỉ ảo, đơn COD 5-10 triệu. Anh em cẩn thận kiểm tra kỹ trước khi giao."],
[1,"Ai biết cách liên hệ bộ phận hỗ trợ GHTK nhanh nhất? Gọi hotline chờ 30 phút không ai nghe."],
[1,"Review: Dùng GHTK 2 năm rồi, ưu điểm là đơn nhiều, nhược điểm là app hay lỗi và support chậm."],
[1,"Mẹo tiết kiệm xăng: Sắp xếp đơn theo khu vực, giao theo vòng tròn thay vì chạy qua chạy lại. Tiết kiệm 30% xăng."],
[1,"Hôm nay gặp khách dễ thương quá, order trà sữa xong còn mua thêm 1 ly cho shipper. Cảm ơn khách!"],
[1,"Có ai biết GHTK có chương trình thưởng Tết năm nay không? Năm ngoái được 500k bonus."],
[1,"Chia sẻ: Nên mang theo bút và giấy note. Khi khách không nghe máy, dán note lên cửa rất hiệu quả."],
[1,"Lỗi app GHTK: Đơn hiện giao thành công nhưng tiền chưa về ví. Ai gặp tình trạng này chưa?"],
[1,"Kinh nghiệm giao hàng chung cư: Gọi bảo vệ trước, xin phép để xe ở lobby, đi thang máy lên. Nhanh hơn nhiều so với đợi khách xuống."],
[1,"GHTK vừa thêm tính năng ước tính thu nhập theo ngày. Khá tiện để theo dõi, anh em cập nhật app mới nhé."],
[1,"Hỏi: Shipper mới GHTK cần đặt cọc bao nhiêu? Mình nghe nói 500k nhưng không chắc."],
[1,"Mẹo: Chụp ảnh mỗi đơn hàng trước khi giao cho khách. Phòng trường hợp khách nói hàng bị hư, mình có bằng chứng."],
[1,"Đơn hoàn hôm nay nhiều quá, 8/40 đơn bị hoàn. Chủ yếu do khách đổi ý, không phải lỗi shipper."],
[1,"Chia sẻ: Đầu tư 1 cái giá đỡ điện thoại tốt (200-300k) sẽ giúp xem bản đồ an toàn hơn nhiều."],
[1,"GHTK bắt đầu áp dụng KPI mới từ tháng 4. Tối thiểu 30 đơn/ngày để giữ rank. Anh em chuẩn bị nhé."],
[1,"Kinh nghiệm: Giao hàng khu công nghiệp nên đi giờ nghỉ trưa (11h30-13h), công nhân có mặt nhận hàng."],
[1,"Cảm ơn anh em trong nhóm! Nhờ các mẹo chia sẻ mà tháng này mình tăng thu nhập 20% so với tháng trước."],

// Group 2: Grab-Be-Gojek (25 posts)
[2,"So sánh thu nhập Grab vs Be vs Gojek tháng 3: Grab 8-12tr, Be 6-9tr, Gojek 7-10tr. Grab vẫn nhiều đơn nhất nhưng phí cao hơn."],
[2,"Mẹo chạy Grab: Nên online vào khung 11h-13h và 17h-20h. Đây là giờ cao điểm, đơn nhiều và có surge price."],
[2,"Be vừa update chương trình thưởng mới: Hoàn thành 40 chuyến/tuần được bonus 500k. Khá hấp dẫn!"],
[2,"Hỏi: Chạy GrabFood có cần đăng ký riêng hay dùng chung tài khoản GrabBike được?"],
[2,"Kinh nghiệm: Khi chạy Gojek, nên tắt app 30 phút rồi bật lại nếu không nhận được đơn. Hệ thống sẽ ưu tiên lại."],
[2,"Cảnh báo: Khu vực Thủ Đức nhiều đơn ảo Grab, đến nơi khách cancel. Mất thời gian mà không được đền bù."],
[2,"So sánh phí chiết khấu: Grab 20-28%, Be 15-20%, Gojek 18-25%. Be đang có mức phí tốt nhất."],
[2,"Tips: Luôn giữ rating trên 4.8 để nhận đơn ưu tiên. Dưới 4.5 là bị hạn chế đơn rồi."],
[2,"Gojek vừa thêm GoSend Express - giao trong 1 giờ. Phí shipper được 25k/đơn, khá ổn."],
[2,"Ai biết cách đăng ký chạy đa nền tảng (Grab + Be + Gojek cùng lúc)? Có vi phạm hợp đồng không?"],
[2,"Review áo mưa Grab phát miễn phí: Chất lượng tạm ổn, dùng được 3-4 tháng. Nên mua thêm 1 cái dự phòng."],
[2,"Mẹo: Chạy GrabFood nên chuẩn bị túi giữ nhiệt riêng, không dùng túi Grab phát. Giữ nhiệt tốt hơn nhiều."],
[2,"Hôm nay chạy Be được 35 chuyến, thu nhập 650k sau trừ phí. Khu vực Quận 1 - Quận 3 nhiều đơn nhất."],
[2,"Kinh nghiệm xử lý khi bị đánh giá 1 sao: Liên hệ support ngay, cung cấp bằng chứng. 90% sẽ được xóa review."],
[2,"Grab vừa tăng giá cước, khách hàng phản ứng dữ quá. Nhiều người chuyển sang Be với Gojek rồi."],
[2,"Tips an toàn: Không nhận chở khách có hành lý quá lớn hoặc đáng ngờ. An toàn là trên hết."],
[2,"Chia sẻ: Mình chạy Grab 3 năm, thu nhập trung bình 10-12tr/tháng. Đủ sống nhưng phải chăm chỉ."],
[2,"Gojek có chương trình bảo hiểm tai nạn miễn phí cho tài xế. Anh em nhớ kích hoạt trong app nhé."],
[2,"Hỏi: Grab có cho đổi khu vực hoạt động không? Mình muốn chuyển từ HCM ra Hà Nội."],
[2,"Mẹo: Sạc dự phòng 20000mAh là must-have. Chạy cả ngày tốn pin lắm, hết pin là mất thu nhập."],
[2,"Be đang tuyển tài xế mới, thưởng giới thiệu 500k/người. Ai cần link đăng ký inbox mình nhé."],
[2,"So sánh chất lượng khách: Grab khách trung lưu, tip ít. Be khách trẻ, hay cancel. Gojek khách đa dạng."],
[2,"Kinh nghiệm: Nên mang theo dây thun, túi nilong để buộc hàng. Giao đồ ăn mà đổ là mất tiền đền."],
[2,"Cuối tuần chạy GrabFood khu Phú Mỹ Hưng cực kỳ nhiều đơn, toàn đơn 50-100k. Thu nhập gấp đôi ngày thường."],
[2,"Anh em nhớ khai thuế thu nhập cá nhân nhé. Grab, Be, Gojek đều báo thuế rồi, trốn là bị phạt."],
];

$inserted = 0;
$stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content, type, likes_count, comments_count, created_at) VALUES (?, ?, ?, 'post', ?, ?, ?)");

foreach ($posts as $i => $p) {
    $gid = $p[0];
    $content = $p[1];
    $uid = $users[array_rand($users)];
    $likes = rand(3, 80);
    $comments = rand(0, 20);
    // Random date in last 30 days
    $days = rand(0, 30);
    $hours = rand(6, 22);
    $mins = rand(0, 59);
    $date = date('Y-m-d H:i:s', strtotime("-{$days} days -{$hours} hours -{$mins} minutes"));
    
    try {
        $stmt->execute([$gid, $uid, $content, $likes, $comments, $date]);
        $inserted++;
    } catch (Throwable $e) {
        echo "ERR post $i: " . $e->getMessage() . "\n";
    }
}

echo "Batch 1: Inserted $inserted/50 posts (group 1+2)\n";
