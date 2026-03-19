<?php
require_once __DIR__ . '/../includes/db.php';
$d = db();

// 20 bài viết THẬT từ góc nhìn shipper thực tế
$posts = [
  [
    "content" => "Ae ơi chia sẻ kinh nghiệm 2 năm chạy GHTK:\n\n1. Đổ xăng lúc 5-6h sáng, trạm vắng + xăng lạnh = được nhiều hơn\n2. Giữ tốc độ 40-50km/h ổn định, KHÔNG tăng giảm ga liên tục\n3. Tắt máy khi chờ khách >30 giây\n4. Kiểm tra lốp mỗi sáng - lốp non hao xăng 15%\n5. Đổ full bình, không đổ 50k 50k\n\nTiết kiệm được 50-80k xăng/ngày, tháng = 1.5-2 triệu!\n\nAe có mẹo gì hay share thêm 👇",
    "type" => "tips", "image" => "real_1.jpg",
    "province" => "Hồ Chí Minh", "district" => "Tân Bình"
  ],
  [
    "content" => "Checklist đồ nghề shipper mới cần mua (đừng tiết kiệm mấy món này):\n\n✅ Sạc dự phòng 10000mAh+ (hết pin = mất đơn)\n✅ Bình nước 1 lít (dehydration = giảm phản xạ)\n✅ Áo mưa BỘ loại tốt (áo cánh dơi = ướt hàng = đền 500k)\n✅ Túi giữ nhiệt cho đồ ăn\n✅ Dây thun/buộc hàng cồng kềnh\n✅ Thuốc đau đầu + dầu gió\n✅ Găng tay chống nắng\n✅ Kính chống bụi\n\nTổng đầu tư ~300k nhưng tiết kiệm cả triệu/tháng. Ae bổ sung thêm gì?",
    "type" => "tips", "image" => "real_6.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Mẹo giao hàng nhanh hơn 30% mà ít ai biết:\n\n1. GỌI KHÁCH TRƯỚC 5 PHÚT khi đến → khách chuẩn bị sẵn → giao xong trong 1 phút\n2. Học thuộc các con hẻm khu hay giao → nhanh hơn GPS 5-10 phút/đơn\n3. Chụp ảnh hàng TRƯỚC khi giao → 5 giây chụp = tránh khiếu nại cả buổi\n4. Nhận 3-4 đơn/chuyến là tối ưu → nhiều hơn = rối route = chậm hơn\n5. Sắp xếp hàng trong cốp theo thứ tự giao → không phải lục tìm\n\nÁp dụng từ khi nào thấy hiệu quả ae? 🚀",
    "type" => "tips", "image" => "real_5.jpg",
    "province" => "Hà Nội", "district" => "Cầu Giấy"
  ],
  [
    "content" => "Hỏi thật ae: GHTK hay GHN trả phí ship cho shipper tốt hơn?\n\nMình đang chạy GHTK được 8 tháng, trung bình 15-25k/đơn tùy quận. Nghe ae khen GHN trả cao hơn + thưởng nhiều hơn.\n\nAi chạy cả 2 cho ý kiến với! Mình đang phân vân chuyển hay chạy song song.\n\n#shipper #GHTK #GHN",
    "type" => "question", "image" => "real_4.jpg",
    "province" => "Hồ Chí Minh", "district" => "Quận 1"
  ],
  [
    "content" => "Thu nhập shipper 2026 thực tế bao nhiêu? Mình chia sẻ trải nghiệm thật:\n\n🏍️ Chạy GHTK khu Tân Bình - Tân Phú (HCM)\n📦 Trung bình: 25-35 đơn/ngày\n💰 Thu: 350-500k/ngày\n⛽ Trừ xăng: ~80-100k\n🔧 Hao mòn xe: ~30k/ngày\n➡️ Ròng: 200-370k/ngày ~ 6-11 triệu/tháng\n\nNgày đông (sale, lễ): 600-800k\nNgày mưa: 150-200k (ít đơn nhưng có thêm phụ phí)\n\nAe ở khu khác thu nhập sao? Share để ae tham khảo! 💰",
    "type" => "discussion", "image" => "real_2.jpg",
    "province" => "Hồ Chí Minh", "district" => "Tân Bình"
  ],
  [
    "content" => "Ae ship J&T cho mình hỏi: hệ thống tính lương mới thay đổi gì?\n\nMình nghe nói từ tháng 3/2026 J&T đổi cách tính phí COD và thưởng KPI. Ai đang chạy J&T confirm giúp với.\n\nĐang phân vân giữa J&T và SPX, cái nào ổn định hơn?\n\n#J&T #SPX #shipper",
    "type" => "question", "image" => "real_11.jpg",
    "province" => "Hồ Chí Minh", "district" => ""
  ],
  [
    "content" => "⚠️ Cảnh báo ae shipper: Mùa mưa sắp tới, 5 điều BẮT BUỘC nhớ:\n\n1. Bọc hàng 2 LỚP nilon (1 lớp vẫn thấm)\n2. Áo mưa bộ, KHÔNG dùng áo cánh dơi\n3. Giảm tốc qua vũng nước - ổ gà ẩn bên dưới\n4. KHÔNG giao khi sấm sét + mưa to = dừng lại chờ\n5. Kiểm tra phanh xe 2 lần/tuần mùa mưa\n\nMùa mưa năm ngoái mình thấy 3 ae bị tai nạn vì đường trơn. An toàn là trên hết! 🙏",
    "type" => "warning", "image" => "real_7.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Ae shipper cẩn thận: Tuần vừa rồi mình gặp 2 đơn lừa đảo kiểu mới.\n\nKhách đặt COD 5 triệu, địa chỉ giao là khu vắng. Giao xong khách nói 'chuyển khoản' rồi show màn hình giả. May mình kiểm tra kỹ mới phát hiện.\n\nCách phòng tránh:\n✅ Đơn COD >1 triệu → đếm tiền mặt tại chỗ\n✅ Không nhận chuyển khoản khi giao\n✅ Đơn khu vắng → gọi xác nhận trước\n✅ Chụp ảnh + quay video khi giao\n\nAe cẩn thận nhé! ⚠️",
    "type" => "warning", "image" => "real_4.jpg",
    "province" => "Hồ Chí Minh", "district" => "Quận 12"
  ],
  [
    "content" => "Chuyện chỉ shipper mới hiểu 😂\n\n1. GPS chỉ vào ngõ cụt, đứng giữa đường nhìn bản đồ như người ngoài hành tinh\n2. 'Anh ơi em ở tầng 20, không có thang máy' 💀\n3. Khách ghi 'để trước cửa' nhưng cửa nào trong 10 cái cửa?\n4. Giao xong khách rate 5 sao + tip 2k... cũng là tình cảm 😅\n5. Trời nắng 40°C nhưng khách vẫn nhắn 'anh ship nhanh nha'\n6. Đang chạy 50km/h thì app báo 'có đơn mới cách 200m'\n\nAe có chuyện gì hài hước share thêm đi! 😂",
    "type" => "fun", "image" => "real_8.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Tâm sự: Làm shipper 3 năm, đây là điều mình học được:\n\n1. Kiên nhẫn là kỹ năng quan trọng nhất\n2. Sức khỏe là vốn - nghỉ ngơi đúng giờ, ăn uống đủ bữa\n3. Tiết kiệm ít nhất 30% thu nhập mỗi tháng\n4. Bảo dưỡng xe định kỳ - 1 lần hỏng xe = mất cả ngày\n5. Đừng so sánh thu nhập với người khác - mỗi khu vực khác nhau\n6. Luôn lịch sự với khách - uy tín = nhiều đơn hơn\n7. Có kế hoạch B - nghề shipper không bền vững mãi\n\nNghề nào cũng có cái khó, quan trọng là mình làm tốt phần của mình 💪",
    "type" => "discussion", "image" => "real_18.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "So sánh thực tế 5 hãng ship sau 2 năm chạy tất cả:\n\n🟢 GHTK: Đơn nhiều nhất, phí/đơn trung bình, app ổn định\n🟠 GHN: Phí cao hơn GHTK, thưởng tốt, đơn ít hơn\n🔴 J&T: Đơn ít, phí OK, hay thay đổi chính sách\n🟣 SPX: Đơn Shopee nhiều, phí thấp, áp lực KPI cao\n🟡 Ninja Van: Đơn ít nhất, phí tạm, ít áp lực\n\nKhuyên ae mới: Chạy GHTK hoặc GHN trước cho quen, sau đó thêm hãng phụ.\n\nAe chạy hãng nào, chia sẻ thêm 👇",
    "type" => "review", "image" => "real_3.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Review SPX Express (Shopee) sau 6 tháng chạy:\n\nƯu điểm:\n✅ Đơn nhiều nhờ Shopee\n✅ App dễ dùng, scan nhanh\n✅ Đơn gần nhà nhiều\n✅ Được chọn ca linh hoạt\n\nNhược điểm:\n❌ Phí/đơn thấp hơn GHTK/GHN khá nhiều\n❌ KPI áp lực (phải giao đủ số đơn/ngày)\n❌ Đơn hoàn nhiều (khách Shopee hay hủy)\n❌ COD chậm đối soát 3-5 ngày\n\nTổng kết: OK cho ae muốn đơn nhiều, chấp nhận phí thấp. Nếu muốn phí cao hơn → GHTK/GHN.\n\nAe đang chạy SPX thấy sao? 📦",
    "type" => "review", "image" => "real_12.jpg",
    "province" => "Hồ Chí Minh", "district" => ""
  ],
  [
    "content" => "Ae ship khu Quận 7, Nhà Bè (HCM) lưu ý:\n\n📍 Đường Nguyễn Thị Thập: hay kẹt 17-19h, đi đường Lê Văn Lương thay thế\n📍 Khu Phú Mỹ Hưng: bảo vệ hay chặn, gọi khách ra cổng hoặc xin mã ra vào\n📍 Nhà Bè: đường nhỏ GPS hay sai, hỏi dân địa phương nhanh hơn\n📍 Cầu Phú Mỹ: tránh giờ cao điểm 7-8h sáng + 17-18h chiều\n📍 Khu chế xuất Tân Thuận: cần CMND để vào, chuẩn bị sẵn\n\nAe khu khác share tips khu vực mình đi 👇 📍",
    "type" => "tips", "image" => "real_13.jpg",
    "province" => "Hồ Chí Minh", "district" => "Quận 7"
  ],
  [
    "content" => "Tips ship khu Cầu Giấy - Từ Liêm (Hà Nội):\n\n📍 Khu Mỹ Đình: nhiều chung cư cao tầng, học thuộc số tòa + cửa nào vào\n📍 Đường Phạm Hùng: kẹt cứng 17-19h, đi đường Trần Thái Tông hoặc Dương Đình Nghệ\n📍 Khu ĐH: sinh viên hay đặt đồ ăn 11-13h, chuẩn bị sẵn khu vực này\n📍 Keangnam: bảo vệ strict, gọi khách xuống sảnh\n📍 Mùa đông HN: 5h chiều đã tối, bật đèn xe sớm + mặc áo phản quang\n\nAe ship HN chia sẻ thêm khu vực mình đi nhé! 📍",
    "type" => "tips", "image" => "real_14.jpg",
    "province" => "Hà Nội", "district" => "Cầu Giấy"
  ],
  [
    "content" => "GHTK vừa điều chỉnh phụ phí xăng dầu từ 12/03/2026. Ae check app cập nhật nhé.\n\nTheo mình thấy:\n- Phí giảm nhẹ so với tháng trước\n- Áp dụng cho tất cả dịch vụ\n- Có hiệu lực từ 12h ngày 12/3\n\nNhưng giá xăng thực tế vẫn cao. Ae tiết kiệm xăng bằng cách:\n1. Giữ tốc độ ổn định 40-50km/h\n2. Tắt máy khi chờ >30s\n3. Kiểm tra áp suất lốp mỗi sáng\n4. Plan route trước khi chạy\n\nAe thấy phí mới ảnh hưởng thu nhập nhiều không? 📰",
    "type" => "news", "image" => "real_15.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Năm 2026, TMDT Việt Nam đạt 25 tỷ USD. Điều này có nghĩa gì cho ae shipper?\n\n📈 Đơn hàng sẽ TĂNG mạnh - thêm nhiều việc\n📈 Nhiều hãng ship mới = cạnh tranh = phí tốt hơn cho shipper\n📈 Khách mua online nhiều hơn = việc không thiếu\n\nNhưng cũng có thách thức:\n⚠️ Nhiều shipper mới = cạnh tranh đơn\n⚠️ Hãng ship có thể ép phí xuống\n⚠️ Yêu cầu giao nhanh hơn (4h, 2h)\n⚠️ Robot/drone giao hàng sẽ xuất hiện\n\nAe nghĩ sao? Nghề shipper 5 năm nữa sẽ như thế nào? 📈",
    "type" => "discussion", "image" => "real_16.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Gửi ae shipper đang mệt mỏi:\n\nNắng cũng đi, mưa cũng đi. Khách hủy đơn cũng phải đi tiếp. Có hôm chạy từ 7h sáng đến 9h tối mà chỉ được 250k.\n\nNhưng nhớ rằng:\n💪 Ae đang kiếm tiền bằng sức lao động chân chính\n💪 Không ai giàu mãi, không ai khó mãi\n💪 Mỗi đơn giao thành công = 1 khách hàng vui\n💪 Ae tự do hơn 90% người làm văn phòng\n\nNghỉ ngơi khi cần. Uống nước đủ. Ăn cơm đúng giờ. Sức khỏe là vốn.\n\nAe shipper, hôm nay ae chạy được bao nhiêu đơn rồi? 💪",
    "type" => "discussion", "image" => "real_17.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Bảo hiểm cho shipper - ae đang dùng gói nào?\n\nMình chạy 2 năm rồi mới biết là GHTK có bảo hiểm tai nạn cho shipper. Nhưng mức đền khá thấp.\n\nMình đang tìm hiểu:\n🛡️ Bảo hiểm tai nạn cá nhân (~500k/năm)\n🛡️ Bảo hiểm xe máy bắt buộc (đã có)\n🛡️ Bảo hiểm sức khỏe tự nguyện\n\nAe nào đã mua bảo hiểm riêng cho nghề ship cho ý kiến với. Đáng tiền không?\n\n#shipper #baohiem",
    "type" => "question", "image" => "real_19.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "5 lỗi xe máy shipper hay gặp + cách tự sửa tạm:\n\n🔧 1. Xẹp lốp giữa đường → mang theo bơm mini (50k trên Shopee)\n🔧 2. Bugi chết → thay bugi dự phòng (15k, 2 phút thay)\n🔧 3. Xích/sên chùng → siết lại bằng cờ lê 14mm\n🔧 4. Phanh yếu → kiểm tra + thay má phanh (30k, tiệm nào cũng có)\n🔧 5. Đèn cháy → mang bóng đèn dự phòng (10k)\n\nHọc sửa xe cơ bản = không mất cả buổi chờ tiệm. Đầu tư 30 phút học YouTube = tiết kiệm hàng triệu/năm.\n\nAe có mẹo sửa xe nào hay share thêm! 🔧",
    "type" => "tips", "image" => "real_20.jpg",
    "province" => "", "district" => ""
  ],
  [
    "content" => "Review GHTK sau 1 năm chạy full-time:\n\n🟢 Ưu điểm:\n- Đơn nhiều nhất trong các hãng\n- App ổn định, ít crash\n- Hỗ trợ CSKH qua app nhanh\n- Có bảo hiểm cơ bản\n- Được chọn khu vực giao\n\n🔴 Nhược điểm:\n- Phí/đơn thấp hơn GHN\n- Đơn hoàn không được tính công\n- Áp lực giao đúng giờ\n- Hệ thống điểm phạt strict\n- COD đối soát T+3\n\nTổng kết: Phù hợp ae muốn chạy đều, đơn nhiều. Nếu muốn phí cao → GHN.\n\nAe GHTK đồng ý không? 🟢",
    "type" => "review", "image" => "real_9.jpg",
    "province" => "", "district" => ""
  ],
];

// Random users (real seed users)
$userIds = range(3, 102);
shuffle($userIds);

$count = 0;
$pdo = $d->getConnection();

foreach ($posts as $i => $post) {
    $userId = $userIds[$i % count($userIds)];
    $image = 'uploads/posts/real/' . $post['image'];
    
    // Random time in last 7 days
    $daysAgo = rand(0, 6);
    $hoursAgo = rand(0, 23);
    $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
    
    // Random engagement
    $likes = rand(15, 120);
    $comments = rand(5, 45);
    $shares = rand(2, 20);
    
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, type, province, district, likes_count, comments_count, shares_count, `status`, is_real_content, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, ?)");
    
    try {
        $stmt->execute([
            $userId,
            $post['content'],
            $image,
            $post['type'] ?? 'discussion',
            $post['province'] ?? '',
            $post['district'] ?? '',
            $likes,
            $comments,
            $shares,
            $createdAt,
        ]);
        $count++;
        echo "✅ Post $count: " . substr($post['content'], 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        // Try without is_real_content column
        $stmt2 = $pdo->prepare("INSERT INTO posts (user_id, content, image, type, province, district, likes_count, comments_count, shares_count, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt2->execute([
            $userId,
            $post['content'],
            $image,
            $post['type'] ?? 'discussion',
            $post['province'] ?? '',
            $post['district'] ?? '',
            $likes,
            $comments,
            $shares,
            $createdAt,
        ]);
        $count++;
        echo "✅ Post $count (fallback): " . substr($post['content'], 0, 50) . "...\n";
    }
}

echo "\n🎉 Done! Inserted $count real posts\n";
echo "Total posts: " . $d->fetchOne("SELECT COUNT(*) as cnt FROM posts WHERE `status`='active'")['cnt'] . "\n";
