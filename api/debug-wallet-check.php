<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain');
$users = $pdo->query("SELECT id FROM users WHERE id > 1 AND status='active' ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);

$posts = [
// Group 3: Shipper Sài Gòn (10)
[3,"Cảnh báo: Đường Nguyễn Hữu Cảnh ngập nặng từ 4h chiều. Anh em tránh khu vực Vinhomes, đi vòng qua Tôn Đức Thắng."],
[3,"Khu vực Quận 12 hôm nay đơn nhiều lắm, chạy từ sáng tới giờ được 30 đơn rồi. Ai rảnh qua đây nhé!"],
[3,"Hỏi: Đi từ Quận 7 sang Thủ Đức đường nào nhanh nhất lúc 5h chiều? Tránh kẹt xe."],
[3,"Chia sẻ: Quán cơm ngon rẻ cho shipper ở 145 Lý Thường Kiệt Q.Tân Bình, 25k/phần đầy đặn."],
[3,"Mưa to Quận Bình Thạnh, đường Điện Biên Phủ ngập 30cm. Anh em cẩn thận!"],
[3,"Tips: Giao hàng khu Phú Mỹ Hưng nên mặc áo sạch sẽ, bảo vệ hay kiểm tra kỹ."],
[3,"Ai biết chỗ sửa xe máy uy tín ở Gò Vấp không? Honda mình bị hao xăng bất thường."],
[3,"Cuối năm đơn hàng tăng 50%, ai rảnh tranh thủ chạy thêm. Kiếm tiền Tết nào anh em!"],
[3,"Chia sẻ bản đồ các điểm sạc xe điện miễn phí ở SG cho anh em chạy VinFast."],
[3,"Cảnh báo: Camera phạt nguội mới lắp ở ngã tư Hàng Xanh, anh em chạy đúng tốc độ nhé."],

// Group 4: Shipper Hà Nội (10)
[4,"Cầu Nhật Tân kẹt cứng từ 7h sáng. Ai đi Đông Anh nên đi cầu Thăng Long."],
[4,"Chia sẻ: Quán phở ngon ở Hoàn Kiếm, 35k/bát, ăn sáng xong chạy đơn cả ngày."],
[4,"Mùa đông HN lạnh 8 độ, chia sẻ tips giữ ấm: mang 2 lớp găng tay, mặc áo gió bên ngoài."],
[4,"Khu Cầu Giấy đơn nhiều nhưng đường hẹp, hẻm sâu. Nên dùng xe máy nhỏ gọn."],
[4,"Ai biết GHTK Hà Nội có mấy kho? Mình mới chuyển từ SG ra, chưa quen."],
[4,"Tips: Giao hàng khu chung cư Times City nên gửi xe ở tầng hầm, đi thang máy lên. Nhanh hơn đợi khách xuống."],
[4,"Cảnh báo ngập: Khu vực Đại Từ - Hoàng Mai ngập sau mưa lớn. Tránh đi qua."],
[4,"Chia sẻ: App Zalo Maps chỉ đường ở HN chính xác hơn Google Maps, đặc biệt khu phố cổ."],
[4,"Hỏi: Shipper HN thu nhập trung bình bao nhiêu/tháng? Mình đang cân nhắc chuyển nghề."],
[4,"Đường Giải Phóng sửa đoạn gần Bến xe Giáp Bát, kẹt kinh khủng. Đi đường Trường Chinh thay."],

// Group 5: Review Đồ Ship (10)
[5,"Review túi giữ nhiệt Lalamove 35L: Giá 150k, chất lượng 7/10. Giữ nhiệt OK trong 1.5 tiếng, nhưng khóa kéo hay kẹt."],
[5,"So sánh 3 loại bao tay chống nắng: Cao su (15k) vs vải cotton (30k) vs vải thể thao (50k). Cá nhân mình thích loại vải thể thao nhất."],
[5,"Review giá đỡ điện thoại Baseus: 180k, chắc chắn, xoay 360 độ. Dùng 6 tháng chưa bị lỏng. Recommend!"],
[5,"Chia sẻ: Mua áo mưa 2 lớp ở Shopee 120k, dùng tốt hơn áo mưa hãng phát miễn phí nhiều."],
[5,"Review mũ bảo hiểm fullface Royal M136: 450k, có kính chống sương. Đội cả ngày không đau đầu."],
[5,"So sánh sạc dự phòng: Anker 10000mAh (350k) vs Xiaomi 20000mAh (300k). Xiaomi pin nhiều hơn, Anker sạc nhanh hơn."],
[5,"Review thùng sau xe máy GIVI E22N: 680k, vừa 2 hộp pizza lớn. Khóa chắc, chống nước tốt."],
[5,"Mua đèn LED gắn xe 80k trên Tiki, sáng gấp 3 lần đèn zin. Giao hàng đêm an toàn hơn nhiều."],
[5,"Review app Waze cho shipper: Cảnh báo camera, kẹt xe realtime. Tốt hơn Google Maps cho việc tránh tắc đường."],
[5,"Chia sẻ: Đầu tư 1 đôi giày chống nước 200k. Mùa mưa không lo chân ướt, giao hàng thoải mái."],

// Group 6: Confession Shipper (10)
[6,"Confession: Hôm nay giao đơn cho người yêu cũ mà không biết. Nhìn tên địa chỉ quen quen, tới nơi đúng là cô ấy. Awkward vl."],
[6,"Confession: Làm shipper 2 năm, tiết kiệm được 80 triệu. Sắp đặt cọc mua nhà trả góp. Nghề shipper tuy vất vả nhưng kiếm được."],
[6,"Confession: Khách cho tip 100k vì giao hàng dưới mưa. Cảm động quá, shipper cũng cần được trân trọng."],
[6,"Confession: Bố mẹ không ủng hộ mình làm shipper, nói là nghề không tương lai. Nhưng mình kiếm 15tr/tháng, hơn nhiều bạn bè cùng trang lứa."],
[6,"Confession: Hôm nay giao nhầm đơn, phải chạy 15km quay lại đổi. Mệt nhưng khách thông cảm nên cũng vui."],
[6,"Confession: Chạy shipper gặp được vợ. Giao hàng cho cô ấy 3 lần, lần thứ 4 xin số điện thoại. Giờ cưới nhau 1 năm rồi."],
[6,"Confession: Mới bị tai nạn nhẹ khi đang giao hàng. Xe tông nhau ở ngã tư, may không sao. Anh em chạy cẩn thận nhé."],
[6,"Confession: Khách hàng bảo hàng bị hỏng nhưng mình chắc chắn giao nguyên vẹn. Camera hành trình cứu mình 1 vụ."],
[6,"Confession: Tết năm nay không về quê vì tranh thủ chạy đơn. Thu nhập Tết gấp 3 ngày thường. Gửi tiền về cho gia đình."],
[6,"Confession: Làm shipper mới hiểu giá trị đồng tiền. Trước làm văn phòng tiêu xài phung phí, giờ quý từng đồng."],

// Group 7: Mẹo Tiết Kiệm Xăng (5)
[7,"Mẹo #1: Giữ lốp xe đúng áp suất (2.0-2.5 bar). Lốp non hao xăng 10-15%. Kiểm tra mỗi tuần."],
[7,"Mẹo #2: Không tăng ga đột ngột khi khởi hành. Tăng từ từ tiết kiệm 20% xăng so với tăng nhanh."],
[7,"Mẹo #3: Tắt máy khi dừng đèn đỏ trên 30 giây. Nhiều xe Wave/Future có chế độ idling stop."],
[7,"Mẹo #4: Vệ sinh bugi mỗi 3000km. Bugi bẩn làm đốt cháy không hoàn toàn, hao xăng 15%."],
[7,"Mẹo #5: Đổ xăng buổi sáng sớm (6-7h). Xăng lạnh đặc hơn, được nhiều hơn so với đổ buổi trưa."],

// Group 8: J&T SPX Ninja Van (5)
[8,"So sánh: J&T trả 5k/đơn nội thành, SPX 4.5k, Ninja Van 5.5k. Nhưng Ninja Van ít đơn hơn."],
[8,"J&T mới mở kho Quận 9, anh em khu vực đó đăng ký nhận đơn nhé. Đơn nhiều lắm."],
[8,"SPX (Shopee Express) đang tuyển shipper part-time, chỉ cần chạy 3-4h/ngày. Phù hợp sinh viên."],
[8,"Kinh nghiệm chạy Ninja Van: Nên lấy đơn từ 7h sáng, hết đơn sớm là nghỉ sớm."],
[8,"J&T bắt đầu áp dụng scan QR thay vì nhập mã tay. Nhanh hơn nhiều, anh em update app mới nhé."],

// Group 9: Tips Giao Hàng Nhanh (5)
[9,"Tips: Luôn chuẩn bị tiền lẻ khi giao COD. Khách đưa 500k mà mình không thối được là mất thời gian."],
[9,"Tips: Chụp ảnh biển số nhà trước khi giao. Nếu khách phản ánh chưa nhận, mình có bằng chứng."],
[9,"Tips: Sắp xếp đơn theo khu vực trên bản đồ trước khi xuất phát. Tiết kiệm 30-40% quãng đường."],
[9,"Tips: Gọi điện khách trước 15 phút khi sắp tới. Khách chuẩn bị sẵn tiền, nhận nhanh hơn."],
[9,"Tips: Mang theo bút xóa tên + SĐT trên bao bì trước khi giao. Bảo vệ thông tin khách hàng."],

// Group 10: Hỏi Đáp Shipper (5)
[10,"Hỏi: Shipper có cần đóng bảo hiểm xã hội không? Mình chạy tự do, không thuộc công ty nào."],
[10,"Hỏi: Bị CSGT dừng xe khi đang giao hàng, giấy tờ cần mang theo những gì?"],
[10,"Hỏi: Xe máy trên 50cc có cần bằng A2 không? Mình đang chạy Wave 110cc."],
[10,"Hỏi: Đơn hàng bị mất trong quá trình giao, trách nhiệm thuộc shipper hay sàn?"],
[10,"Hỏi: Thu nhập shipper có phải đóng thuế TNCN không? Ngưỡng bao nhiêu mới phải đóng?"],

// Group 11: Shipper Đà Nẵng (5)
[11,"Đà Nẵng mùa này gió lớn, anh em chạy cầu Rồng, cầu Sông Hàn cẩn thận nhé."],
[11,"Chia sẻ: Quán mì Quảng ngon ở Hải Châu, 30k/tô. Ăn no chạy đơn cả ngày."],
[11,"Khu vực Ngũ Hành Sơn nhiều resort, giao hàng cho khách du lịch tip hậu lắm."],
[11,"Cảnh báo: Đường Hòa Xuân mới mở, Google Maps chưa cập nhật. Đi theo biển chỉ dẫn nhé."],
[11,"Hội An cuối tuần đông khách, đơn GrabFood tăng gấp 3. Ai rảnh chạy qua đó."],

// Group 12: Đồ Nghề Shipper (5)
[12,"Review balo giao hàng 40L: Giá 250k Shopee, vừa 2 hộp pizza + 3 ly trà sữa. Có ngăn giữ nhiệt riêng."],
[12,"So sánh 5 loại bọc yên xe chống nóng: Giá từ 30k-150k. Loại 80k mesh lưới là tốt nhất."],
[12,"Review kính chống nắng cho shipper: UV400 giá 50k trên Lazada. Đeo cả ngày không đau mắt."],
[12,"Chia sẻ: Mua dây rút inox 10k/bó để buộc hàng. Chắc hơn dây thun, dùng lại được nhiều lần."],
[12,"Review loa bluetooth mini gắn xe: JBL Go 3 (700k) vs Xiaomi (200k). JBL âm thanh tốt hơn nhưng Xiaomi đủ dùng."],
];

$inserted = 0;
$stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content, type, likes_count, comments_count, created_at) VALUES (?, ?, ?, 'post', ?, ?, ?)");

foreach ($posts as $i => $p) {
    $gid = $p[0];
    $content = $p[1];
    $uid = $users[array_rand($users)];
    $likes = rand(5, 120);
    $comments = rand(1, 25);
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

// Update post_count for all groups
$pdo->exec("UPDATE `groups` g SET post_count = (SELECT COUNT(*) FROM group_posts WHERE group_id = g.id AND status = 'active')");

echo "Batch 2: Inserted $inserted/60 posts (groups 3-12)\n";

// Final count
$total = $pdo->query("SELECT COUNT(*) as c FROM group_posts WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
echo "Total posts in DB: " . $total['c'] . "\n";

// Per group
$perGroup = $pdo->query("SELECT g.name, g.post_count, (SELECT COUNT(*) FROM group_posts WHERE group_id=g.id AND status='active') as real_count FROM `groups` g ORDER BY g.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($perGroup as $pg) {
    echo "  " . $pg['name'] . ": " . $pg['real_count'] . " posts\n";
}
