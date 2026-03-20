<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();
$pdo = $d->getConnection();

$posts = [
  // === SÀI GÒN specific ===
  ["Ae ship khu Gò Vấp - Bình Thạnh (HCM) lưu ý:\n\n📍 Ngã tư Hàng Xanh: kẹt cứng 7-9h + 17-19h → đi đường Điện Biên Phủ hoặc Nguyễn Xí\n📍 Phan Văn Trị: đường nhỏ hay ngập mùa mưa\n📍 Chung cư Cityland: bảo vệ OK nhưng phải gọi cư dân xuống\n📍 Khu Bạch Đằng: tìm chỗ đậu xe khó, ship giờ trưa tốt hơn\n📍 Chợ Bà Chiểu: đông đúc buổi sáng, vòng qua đường khác\n\nAe ship khu khác share tips! 📍", "tips", "Hồ Chí Minh", "Gò Vấp"],

  // === HÀ NỘI specific ===
  ["Tips ship khu Hoàn Kiếm - Hai Bà Trưng (HN):\n\n📍 Phố cổ: xe máy hạn chế nhiều tuyến, check bảng cấm trước\n📍 Khu Vincom Bà Triệu: đậu xe tầng hầm, lấy phiếu\n📍 Chung cư Times City: bảo vệ cho vào nhưng phải ghi biển số\n📍 Khu Kim Liên - Phương Mai: đường nhỏ, xe bus nhiều\n📍 Phố Huế - Bạch Mai: kẹt 17-19h → đi Lê Đại Hành\n\nAe HN share kinh nghiệm! 📍", "tips", "Hà Nội", "Hoàn Kiếm"],

  // === ĐÀ NẴNG ===
  ["Ae ship Đà Nẵng chia sẻ kinh nghiệm:\n\n📍 Cầu Rồng: đẹp nhưng kẹt cuối tuần tối\n📍 Khu Sơn Trà: đường dốc, cẩn thận hàng dễ vỡ\n📍 Hải Châu: đông đơn nhất, ship quanh đây hiệu quả\n📍 Mùa bão: tuyệt đối không ship khi gió >60km/h\n\nĐà Nẵng có ae ship hãng nào đông nhất? Comment nhé!", "discussion", "Đà Nẵng", "Hải Châu"],

  // === Grab/Be driver tips ===
  ["So sánh ship đồ ăn: Grab vs Be vs Ahamove vs GoFood\n\n🟢 GrabFood: đơn nhiều nhất, phí 12-20k/đơn, phải chờ nhà hàng\n🟡 BeFood: đơn ít hơn, phí tương đương, app hay lag\n🟠 Ahamove: phí cao nhất 15-30k, đơn ít, thoải mái giờ\n🟢 GoFood: đơn khá, phí OK, thưởng ngày\n\nMình chạy GrabFood là chính vì đơn đều. Ae chạy app nào? 🍕", "review", "Hồ Chí Minh", ""],

  // === Xe điện cho shipper ===
  ["Ae ơi, ai đang ship bằng xe điện?\n\nMình thấy ngày càng nhiều ae chuyển sang xe điện (VinFast, Yadea, Datbike). Hỏi ae đang dùng:\n\n1. Dùng xe gì, pin đi được bao nhiêu km?\n2. Sạc ở đâu giữa ca?\n3. Chi phí điện vs xăng tiết kiệm bao nhiêu?\n4. Có hạn chế gì khi ship bằng xe điện?\n\nMình đang cân nhắc đổi xe. Ae chia sẻ giúp! ⚡🏍️", "question", "", ""],

  // === Kinh nghiệm ship Shopee ===
  ["Kinh nghiệm ship đơn Shopee (SPX) cho ae mới:\n\n1. LUÔN check hàng trước khi nhận từ kho → hàng thiếu/sai = khiếu nại về mình\n2. Đơn freeship khách hay hủy → giao nhanh nhất có thể\n3. COD Shopee đối soát chậm 3-5 ngày → chuẩn bị vốn xoay\n4. Đơn hoàn: mang lại kho NGAY, không giữ → bị phạt\n5. Giờ cao điểm SPX: 10-12h sáng nhận đơn từ kho\n\nAe SPX bổ sung thêm! 📦", "tips", "Hồ Chí Minh", ""],

  // === Sức khỏe shipper ===
  ["Ae shipper chú ý sức khỏe nhé!\n\nMình chạy 2 năm, bắt đầu bị:\n- Đau lưng (ngồi xe lâu)\n- Đau cổ tay (cầm lái + bấm điện thoại)\n- Mắt mờ (nhìn GPS liên tục)\n- Khô da (nắng gió)\n\nCách phòng:\n✅ Tập giãn cơ 10 phút mỗi sáng\n✅ Nghỉ 15 phút mỗi 2 giờ chạy\n✅ Uống 2 lít nước/ngày\n✅ Đeo kính + khẩu trang + găng tay\n✅ Khám sức khỏe 6 tháng/lần\n\nSức khỏe = vốn liếng lớn nhất! 💪", "tips", "", ""],

  // === Câu chuyện thành công ===
  ["Từ shipper lên chủ shop - câu chuyện thật:\n\nMình bắt đầu chạy GHTK cách đây 3 năm. Ngày đầu chỉ 8 đơn, 120k.\n\nSau 1 năm:\n- Thuộc lòng tất cả tuyến đường khu mình\n- Quen hết khách quen, tip đều đặn\n- Tiết kiệm được 50 triệu\n\nSau 2 năm:\n- Mở shop bán đồ nghề shipper trên Shopee\n- Vừa ship vừa quản lý shop\n- Thu nhập gấp 2-3 lần chỉ ship\n\nBài học: Đừng coi ship là mãi mãi. Tích lũy kinh nghiệm + vốn → mở rộng.\n\nAe có câu chuyện gì share! 💰", "discussion", "", ""],

  // === Thủ tục pháp lý ===
  ["Shipper cần biết: Quyền lợi pháp lý của mình\n\n📋 Ae có biết:\n1. Shipper có quyền từ chối đơn nguy hiểm\n2. Tai nạn khi giao hàng → hãng phải chịu trách nhiệm (nếu có HĐ)\n3. Hàng mất/vỡ do lỗi hãng → ae không phải đền 100%\n4. Bảo hiểm GHTK chỉ cover tai nạn, không cover bệnh\n5. Ship COD mà khách quỵt → báo hãng xử lý, ae không chịu\n\nNhiều ae không biết quyền của mình. Share cho ae khác biết!\n\n⚠️ Lưu ý: mỗi hãng khác nhau, đọc kỹ hợp đồng.", "warning", "", ""],

  // === Meme/Trend ===
  ["POV: Đời shipper trong 1 ngày ☀️🌧️\n\n6h00: Dậy, check app, hy vọng hôm nay nhiều đơn\n7h00: Ra đường, trời nắng đẹp, tâm trạng vui\n9h00: 5 đơn đầu tiên, smooth\n11h30: Đơn ship đồ ăn, thang máy hỏng, tầng 15 💀\n12h00: Ăn cơm bụi, 30k. Thấy ae shipper khác, gật đầu chào\n14h00: Trời đổ mưa. Bọc hàng. Áo mưa.\n15h00: GPS chỉ vào hẻm cụt lần thứ 3\n17h00: Kẹt xe 45 phút. Ngồi đọc ShipperShop.\n19h00: Đơn cuối cùng. Tip 10k. Vui.\n20h00: Về nhà, tắm, ăn. Check thu nhập: 380k\n\nTag ae hiểu cảm giác này! 😂", "fun", "", ""],
];

$userIds = range(3, 102);
shuffle($userIds);
$count = 0;

$stmt = $pdo->prepare("INSERT INTO posts (user_id, content, images, type, province, district, likes_count, comments_count, shares_count, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");

foreach ($posts as $i => $p) {
    $uid = $userIds[$i % count($userIds)];
    $imgNum = ($i % 20) + 1;
    $img = "uploads/posts/real/real_{$imgNum}.jpg";
    $daysAgo = rand(0, 4);
    $hoursAgo = rand(0, 23);
    $cat = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
    $likes = rand(20, 150);
    $cmts = rand(8, 50);
    $shares = rand(3, 25);
    
    try {
        $stmt->execute([$uid, $p[0], $img, $p[1], $p[2], $p[3], $likes, $cmts, $shares, $cat]);
        $count++;
        echo "✅ $count: " . mb_substr($p[0], 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "❌ " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 Inserted $count more real posts!\n";
echo "Total active: " . $d->fetchOne("SELECT COUNT(*) c FROM posts WHERE `status`='active'")['c'] . "\n";
