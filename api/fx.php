<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

$posts = [
    ['type'=>'tip','content'=>'Mẹo ship nhanh hơn: Luôn kiểm tra đơn trước khi xuất phát, sắp xếp theo tuyến đường. Tiết kiệm 30% thời gian! #meogiaohang #shipper'],
    ['type'=>'review','content'=>'Review app GHTK sau 6 tháng sử dụng: Giao diện dễ dùng, tính tiền nhanh, nhưng đôi khi lag giờ cao điểm. 4/5 sao! ⭐⭐⭐⭐ #review #ghtk'],
    ['type'=>'question','content'=>'Hỏi anh em: Có ai ship khu vực Quận 7 HCM không? Đơn nhiều mà thiếu người, muốn tìm bạn đồng hành. #hoidan #quan7'],
    ['type'=>'discussion','content'=>'Thảo luận: Nên chọn xe máy hay xe đạp điện để ship? Mình thấy xe điện tiết kiệm xăng hơn nhiều nhưng pin hơi yếu. #thaoluan'],
    ['type'=>'confession','content'=>'[Confession] Hôm nay giao nhầm đơn, khách gọi lại mình quay xe 5km sửa. Mệt nhưng khách cảm ơn, thấy vui lắm 😊'],
    ['type'=>'tip','content'=>'Cách giữ đồ ăn nóng khi ship: Dùng túi giữ nhiệt + lót giấy bạc bên trong. Khách nhận đồ còn nóng hổi, rate 5 sao ngay! 🔥 #meohay'],
    ['type'=>'post','content'=>'Hôm nay chạy 45 đơn, kỷ lục cá nhân mới! 💪 Cảm ơn anh em trong nhóm đã chia sẻ kinh nghiệm. Ngày mai phải phá kỷ lục! #shipperlife'],
    ['type'=>'review','content'=>'J&T vs GHN: So sánh 2 hãng sau 1 năm chạy cả hai. J&T phí rẻ hơn, GHN giao nhanh hơn. Tùy khu vực mà chọn! #sosánh'],
    ['type'=>'tip','content'=>'Tiết kiệm xăng mỗi tháng 500k: Tắt máy khi đợi lấy hàng, đi đúng tuyến, không nổ máy idle. Tháng này mình chỉ đổ 800k xăng! 💰'],
    ['type'=>'question','content'=>'Ai biết cách đăng ký shipper Shopee Express không? Mình nghe nói phí tốt hơn SPX bình thường. #hoidan #shopee'],
    ['type'=>'post','content'=>'Trời Sài Gòn mưa to quá, nhưng đơn hàng không đợi ai! Anh em cẩn thận khi chạy mưa nhé, an toàn là trên hết 🌧️ #saigon #muaship'],
    ['type'=>'confession','content'=>'[Confession] 3 năm làm shipper, từ 0 đồng giờ mua được xe mới. Không giàu nhưng đủ sống và tự do. Cảm ơn nghề! 🙏'],
    ['type'=>'tip','content'=>'Cách xử lý khi khách không nghe máy: Gọi 3 lần cách nhau 5 phút, nhắn tin SMS + Zalo. Nếu không được thì ghi chú hoàn. Đừng đợi quá lâu! #kinhnghiem'],
    ['type'=>'discussion','content'=>'Bạn nghĩ sao về việc app tăng phí ship? Mình thấy phí tăng nhưng thu nhập shipper không tăng tương ứng 🤔 #thaoluan #phiship'],
    ['type'=>'post','content'=>'Cập nhật bản đồ khu vực Bình Dương: Đường Mỹ Phước - Tân Vạn đang sửa, tránh đoạn km 8-12. Đi đường ĐT743 thay thế! 📍 #binhduong #giaothong'],
    ['type'=>'review','content'=>'Đánh giá túi giữ nhiệt Lalamove: Chất lượng tốt, giữ nóng 2h, giá 150k. Đáng mua cho shipper đồ ăn! ⭐⭐⭐⭐⭐ #review #phukien'],
    ['type'=>'tip','content'=>'Kinh nghiệm ship đêm: Đeo áo phản quang, gắn đèn LED xe, mang theo pin sạc dự phòng. An toàn + chuyên nghiệp = khách tin tưởng! 🌙'],
    ['type'=>'question','content'=>'Hỏi: Có nên đăng ký nhiều app ship cùng lúc không? Mình đang chạy GHTK + GHN, muốn thêm Ninja Van. #hoidan'],
    ['type'=>'post','content'=>'Khoảnh khắc vui nhất hôm nay: Ship sinh nhật cho bé, bé mở hộp cười tít mắt. Nghề ship tuy mệt nhưng nhiều khoảnh khắc ấm áp 🎂❤️'],
    ['type'=>'tip','content'=>'5 ứng dụng shipper cần có: 1. Google Maps 2. Zalo (liên lạc khách) 3. Banking app 4. App hãng ship 5. ShipperShop (cộng đồng)! 📱 #topapp'],
];

$userIds = range(3, 120);
$provinces = ['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Bình Dương', 'Đồng Nai'];
$districts = ['Quận 1', 'Quận 7', 'Thủ Đức', 'Bình Thạnh', 'Tân Bình', 'Gò Vấp', 'Cầu Giấy', 'Hải Châu'];

$count = 0;
foreach ($posts as $p) {
    $uid = $userIds[array_rand($userIds)];
    $prov = $provinces[array_rand($provinces)];
    $dist = $districts[array_rand($districts)];
    
    try {
        $d->query("INSERT INTO posts (user_id, content, type, province, district, `status`, created_at) VALUES (?, ?, ?, ?, ?, 'active', DATE_SUB(NOW(), INTERVAL ? HOUR))",
            [$uid, $p['content'], $p['type'], $prov, $dist, rand(1, 72)]);
        $count++;
    } catch (Throwable $e) {}
}

echo json_encode(['success' => true, 'seeded' => $count]);
