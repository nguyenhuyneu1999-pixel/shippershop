<?php
error_reporting(E_ALL);
ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();
$pdo = $d->getConnection();

$posts = [
  ["Ae ơi chia sẻ kinh nghiệm 2 năm chạy GHTK:\n\n1. Đổ xăng lúc 5-6h sáng, trạm vắng + xăng lạnh = được nhiều hơn\n2. Giữ tốc độ 40-50km/h ổn định, KHÔNG tăng giảm ga liên tục\n3. Tắt máy khi chờ khách >30 giây\n4. Kiểm tra lốp mỗi sáng - lốp non hao xăng 15%\n5. Đổ full bình, không đổ 50k 50k\n\nTiết kiệm được 50-80k xăng/ngày, tháng = 1.5-2 triệu!\n\nAe có mẹo gì hay share thêm 👇", "tips","real_1.jpg","Hồ Chí Minh","Tân Bình"],
  ["Checklist đồ nghề shipper mới cần mua:\n\n✅ Sạc dự phòng 10000mAh+ (hết pin = mất đơn)\n✅ Bình nước 1 lít\n✅ Áo mưa BỘ loại tốt (áo cánh dơi = ướt hàng = đền 500k)\n✅ Túi giữ nhiệt cho đồ ăn\n✅ Dây thun/buộc hàng cồng kềnh\n✅ Thuốc đau đầu + dầu gió\n✅ Găng tay chống nắng\n✅ Kính chống bụi\n\nTổng đầu tư ~300k nhưng tiết kiệm cả triệu/tháng. Ae bổ sung thêm gì?", "tips","real_6.jpg","",""],
  ["Mẹo giao hàng nhanh hơn 30%:\n\n1. GỌI KHÁCH TRƯỚC 5 PHÚT khi đến → khách chuẩn bị sẵn\n2. Học thuộc các con hẻm khu hay giao → nhanh hơn GPS 5-10 phút\n3. Chụp ảnh hàng TRƯỚC khi giao → tránh khiếu nại\n4. Nhận 3-4 đơn/chuyến là tối ưu\n5. Sắp xếp hàng trong cốp theo thứ tự giao\n\nÁp dụng thấy hiệu quả ngay ae! 🚀", "tips","real_5.jpg","Hà Nội","Cầu Giấy"],
  ["Hỏi thật ae: GHTK hay GHN trả phí ship cho shipper tốt hơn?\n\nMình đang chạy GHTK được 8 tháng, trung bình 15-25k/đơn tùy quận. Nghe ae khen GHN trả cao hơn + thưởng nhiều hơn.\n\nAi chạy cả 2 cho ý kiến với!", "question","real_4.jpg","Hồ Chí Minh","Quận 1"],
  ["Thu nhập shipper 2026 thực tế:\n\n🏍️ Chạy GHTK khu Tân Bình - Tân Phú (HCM)\n📦 Trung bình: 25-35 đơn/ngày\n💰 Thu: 350-500k/ngày\n⛽ Trừ xăng: ~80-100k\n🔧 Hao mòn xe: ~30k/ngày\n➡️ Ròng: 200-370k/ngày ~ 6-11 triệu/tháng\n\nAe ở khu khác thu nhập sao?", "discussion","real_2.jpg","Hồ Chí Minh","Tân Bình"],
  ["Ae ship J&T cho hỏi: hệ thống tính lương mới thay đổi gì? Từ tháng 3/2026 J&T đổi cách tính phí COD và thưởng KPI. Ai đang chạy J&T confirm giúp.", "question","real_11.jpg","Hồ Chí Minh",""],
  ["⚠️ Cảnh báo ae shipper mùa mưa:\n\n1. Bọc hàng 2 LỚP nilon\n2. Áo mưa bộ, KHÔNG áo cánh dơi\n3. Giảm tốc qua vũng nước - ổ gà ẩn dưới\n4. KHÔNG giao khi sấm sét\n5. Kiểm tra phanh xe 2 lần/tuần\n\nAn toàn là trên hết! 🙏", "warning","real_7.jpg","",""],
  ["⚠️ Cẩn thận: Tuần rồi mình gặp 2 đơn lừa đảo kiểu mới.\n\nKhách đặt COD 5 triệu, show màn hình chuyển khoản giả.\n\nCách phòng:\n✅ COD >1 triệu → đếm tiền mặt tại chỗ\n✅ Không nhận chuyển khoản khi giao\n✅ Đơn khu vắng → gọi xác nhận trước\n✅ Chụp ảnh + quay video khi giao", "warning","real_4.jpg","Hồ Chí Minh","Quận 12"],
  ["Chuyện chỉ shipper mới hiểu 😂\n\n1. GPS chỉ vào ngõ cụt\n2. 'Em ở tầng 20, không có thang máy' 💀\n3. Khách ghi 'để trước cửa' nhưng cửa nào?\n4. Rate 5 sao + tip 2k 😅\n5. Trời 40°C khách nhắn 'ship nhanh nha'\n6. App báo đơn mới cách 200m khi đang chạy 50km/h\n\nAe share chuyện vui thêm đi! 😂", "fun","real_8.jpg","",""],
  ["Tâm sự 3 năm làm shipper:\n\n1. Kiên nhẫn là kỹ năng #1\n2. Sức khỏe là vốn\n3. Tiết kiệm 30% thu nhập mỗi tháng\n4. Bảo dưỡng xe định kỳ\n5. Đừng so sánh thu nhập\n6. Lịch sự với khách = nhiều đơn hơn\n7. Có kế hoạch B\n\nNghề nào cũng khó, quan trọng là làm tốt phần mình 💪", "discussion","real_18.jpg","",""],
  ["So sánh 5 hãng ship sau 2 năm:\n\n🟢 GHTK: Đơn nhiều nhất, phí trung bình, app ổn\n🟠 GHN: Phí cao hơn, thưởng tốt, ít đơn hơn\n🔴 J&T: Đơn ít, hay đổi chính sách\n🟣 SPX: Đơn Shopee nhiều, phí thấp, KPI cao\n🟡 Ninja Van: Đơn ít nhất, ít áp lực\n\nAe mới nên chạy GHTK/GHN trước. Ae chạy hãng nào? 👇", "review","real_3.jpg","",""],
  ["Review SPX Express sau 6 tháng:\n\n✅ Đơn nhiều nhờ Shopee\n✅ App dễ dùng\n✅ Đơn gần nhà\n\n❌ Phí thấp hơn GHTK/GHN\n❌ KPI áp lực\n❌ Đơn hoàn nhiều\n❌ COD chậm 3-5 ngày\n\nOK cho ae muốn đơn nhiều, chấp nhận phí thấp.", "review","real_12.jpg","Hồ Chí Minh",""],
  ["Ae ship khu Q7, Nhà Bè (HCM) lưu ý:\n\n📍 Nguyễn Thị Thập: kẹt 17-19h → đi Lê Văn Lương\n📍 Phú Mỹ Hưng: bảo vệ hay chặn, gọi khách ra cổng\n📍 Nhà Bè: GPS hay sai, hỏi dân nhanh hơn\n📍 Cầu Phú Mỹ: tránh 7-8h + 17-18h\n\nAe khu khác share tips đi! 📍", "tips","real_13.jpg","Hồ Chí Minh","Quận 7"],
  ["Tips ship khu Cầu Giấy - Từ Liêm (HN):\n\n📍 Mỹ Đình: nhiều chung cư, học thuộc số tòa\n📍 Phạm Hùng: kẹt 17-19h → đi Trần Thái Tông\n📍 Khu ĐH: sinh viên đặt đồ ăn 11-13h\n📍 Keangnam: bảo vệ strict, gọi khách xuống sảnh\n📍 Mùa đông: 5h chiều tối, bật đèn sớm\n\nAe HN share thêm! 📍", "tips","real_14.jpg","Hà Nội","Cầu Giấy"],
  ["GHTK điều chỉnh phụ phí xăng dầu từ 12/03/2026.\n\n- Phí giảm nhẹ so với tháng trước\n- Áp dụng tất cả dịch vụ\n\nTiết kiệm xăng:\n1. Tốc độ ổn định 40-50km/h\n2. Tắt máy khi chờ >30s\n3. Kiểm tra lốp mỗi sáng\n4. Plan route trước khi chạy\n\nPhí mới ảnh hưởng nhiều không ae? 📰", "news","real_15.jpg","",""],
  ["TMDT VN đạt 25 tỷ USD 2026. Ae shipper nghĩ gì?\n\n📈 Đơn hàng tăng mạnh\n📈 Nhiều hãng ship mới = phí tốt hơn\n📈 Khách online nhiều hơn = không thiếu việc\n\nThách thức:\n⚠️ Nhiều shipper mới = cạnh tranh đơn\n⚠️ Hãng có thể ép phí\n⚠️ Robot/drone giao hàng xuất hiện\n\nNghề shipper 5 năm nữa sẽ thế nào? 📈", "discussion","real_16.jpg","",""],
  ["Gửi ae shipper đang mệt:\n\nNắng cũng đi, mưa cũng đi. Có hôm chạy 7h-21h mà chỉ 250k.\n\nNhưng nhớ:\n💪 Ae kiếm tiền bằng sức lao động chân chính\n💪 Không ai giàu mãi, không ai khó mãi\n💪 Mỗi đơn thành công = 1 khách vui\n💪 Tự do hơn 90% người văn phòng\n\nNghỉ khi cần. Uống nước đủ. Ăn đúng giờ.\n\nHôm nay ae chạy được bao nhiêu đơn? 💪", "discussion","real_17.jpg","",""],
  ["Bảo hiểm cho shipper - ae dùng gói nào?\n\nMình 2 năm mới biết GHTK có bảo hiểm tai nạn nhưng mức đền thấp.\n\nĐang tìm hiểu:\n🛡️ BH tai nạn cá nhân (~500k/năm)\n🛡️ BH xe máy bắt buộc (đã có)\n🛡️ BH sức khỏe tự nguyện\n\nAe mua BH riêng cho nghề ship cho ý kiến với. Đáng tiền không?", "question","real_19.jpg","",""],
  ["5 lỗi xe máy hay gặp + cách tự sửa:\n\n🔧 Xẹp lốp → bơm mini 50k Shopee\n🔧 Bugi chết → bugi dự phòng 15k, 2 phút thay\n🔧 Xích chùng → cờ lê 14mm siết lại\n🔧 Phanh yếu → thay má phanh 30k\n🔧 Đèn cháy → bóng dự phòng 10k\n\nHọc sửa xe cơ bản qua YouTube 30 phút = tiết kiệm hàng triệu/năm! 🔧", "tips","real_20.jpg","",""],
  ["Review GHTK sau 1 năm full-time:\n\n🟢 Ưu điểm:\n- Đơn nhiều nhất\n- App ổn định\n- CSKH qua app nhanh\n- Được chọn khu vực giao\n\n🔴 Nhược điểm:\n- Phí thấp hơn GHN\n- Đơn hoàn không tính công\n- Áp lực giao đúng giờ\n- Hệ thống phạt strict\n- COD đối soát T+3\n\nPhù hợp ae muốn đơn nhiều, chạy đều. Ae GHTK đồng ý? 🟢", "review","real_9.jpg","",""],
];

$userIds = range(3, 102);
shuffle($userIds);
$count = 0;

$stmt = $pdo->prepare("INSERT INTO posts (user_id, content, images, type, province, district, likes_count, comments_count, shares_count, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");

foreach ($posts as $i => $p) {
    $uid = $userIds[$i % count($userIds)];
    $img = 'uploads/posts/real/' . $p[2];
    $daysAgo = rand(0, 6);
    $hoursAgo = rand(0, 23);
    $cat = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
    $likes = rand(15, 120);
    $cmts = rand(5, 45);
    $shares = rand(2, 20);
    
    try {
        $stmt->execute([$uid, $p[0], $img, $p[1], $p[3], $p[4], $likes, $cmts, $shares, $cat]);
        $count++;
        echo "✅ $count: " . mb_substr($p[0], 0, 45) . "...\n";
    } catch (Exception $e) {
        echo "❌ $count: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Inserted $count real posts!\n";
echo "Total active: " . $d->fetchOne("SELECT COUNT(*) c FROM posts WHERE `status`='active'")['c'] . "\n";
