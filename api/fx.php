<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

$posts = [
    ['type'=>'tip','content'=>'Bảo quản điện thoại khi ship mưa: Bọc túi nilon, mua giá đỡ chống nước, bật GPS trước khi đi. Tránh dùng touchscreen tay ướt! #meohay #muaship'],
    ['type'=>'review','content'=>'So sánh Grab Express vs Ahamove: Grab phí thấp hơn 10-15%, Ahamove giao nhanh hơn trong nội thành. Cả 2 đều OK cho đơn nhỏ. #review'],
    ['type'=>'question','content'=>'Bạn nào có kinh nghiệm ship hàng đông lạnh không? Cần mua thùng xốp loại nào, đá gel hay đá thường? #hoidan #donglanh'],
    ['type'=>'post','content'=>'Update giá xăng hôm nay 23/3: RON95 tăng 500đ/lít. Anh em tính lại chi phí cho hợp lý nhé! 💰⛽ #giaxang #chiphi'],
    ['type'=>'tip','content'=>'Cách tăng rating 5 sao: 1) Giao đúng giờ 2) Gọi khách trước 5p 3) Đặt hàng nhẹ nhàng 4) Nói cảm ơn. Đơn giản nhưng hiệu quả! ⭐⭐⭐⭐⭐'],
    ['type'=>'confession','content'=>'[Confession] Chạy được 2 năm, tích góp 50 triệu. Sắp mở quán nước nhỏ kết hợp điểm lấy hàng. Ước mơ nhỏ nhưng hạnh phúc 🏠'],
    ['type'=>'discussion','content'=>'Thảo luận: App nào trả phí ship tốt nhất 2026? Mình nghe nói Ninja Van tăng phí cho shipper rồi. Ai confirm? #thaoluan #phi2026'],
    ['type'=>'tip','content'=>'Mẹo chống trộm khi ship: 1) Luôn khóa cốp 2) Không để điện thoại trên ghi-đông 3) Cẩn thận khu vực vắng buổi tối. An toàn nhé! 🔒'],
    ['type'=>'post','content'=>'Đường Nguyễn Hữu Thọ (Q7) đang kẹt cứng do tai nạn. Tránh khu vực từ 15h-18h. Đi Nguyễn Văn Linh thay thế! 📍🚫 #kexe #quan7'],
    ['type'=>'review','content'=>'Review găng tay chống nắng cho shipper (Shopee 35k): Chất vải thoáng, chống UV tốt, có touchscreen ngón trỏ. Mua 2 đôi thay nhau! ⭐⭐⭐⭐'],
    ['type'=>'post','content'=>'Chúc mừng bạn Minh đã hoàn thành 10.000 đơn! 🎉 Ròng rã 3 năm, không bỏ cuộc. Tấm gương cho anh em! 💪 #milestone'],
    ['type'=>'question','content'=>'Có ai biết cách đăng ký shipper cho Tiki không? Web họ đóng form rồi, có cách nào liên hệ? #hoidan #tiki'],
    ['type'=>'tip','content'=>'5 món ăn vặt tiện mang theo: 1) Bánh mì 2) Chuối 3) Sữa hộp 4) Kẹo gừng (chống say) 5) Nước dừa. Rẻ + năng lượng! 🍌🥤'],
    ['type'=>'post','content'=>'Nhóm "Shipper Sài Gòn" vừa tổ chức offline gặp mặt. 30 anh em đến, vui quá! Lần sau ai muốn tham gia inbox nhé 🤝 #offline #saigon'],
    ['type'=>'discussion','content'=>'Shipper nên có bảo hiểm không? Mình vừa bị va chạm nhẹ, may không sao. Nhưng nghĩ lại nếu nặng hơn... Chi phí bảo hiểm bao nhiêu/tháng? #baohiem'],
];

$userIds = range(3, 150);
$provinces = ['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai', 'Cần Thơ'];
$districts = ['Quận 1', 'Quận 7', 'Thủ Đức', 'Bình Thạnh', 'Tân Bình', 'Gò Vấp', 'Cầu Giấy', 'Hải Châu', 'Bình Tân', 'Quận 12'];

$count = 0;
foreach ($posts as $p) {
    $uid = $userIds[array_rand($userIds)];
    $prov = $provinces[array_rand($provinces)];
    $dist = $districts[array_rand($districts)];
    try {
        $d->query("INSERT INTO posts (user_id, content, type, province, district, `status`, likes_count, comments_count, created_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))",
            [$uid, $p['content'], $p['type'], $prov, $dist, rand(0, 30), rand(0, 15), rand(1, 96)]);
        $count++;
    } catch (Throwable $e) {}
}
echo json_encode(['success' => true, 'seeded' => $count]);
