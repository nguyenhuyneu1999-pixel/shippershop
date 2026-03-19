<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// 10 TikTok scripts chuyên nghiệp - có HOOK, nội dung, CTA
$scripts = [
  [
    "title" => "5 mẹo tiết kiệm xăng cho shipper",
    "content" => "🏍️ SHIPPER TIẾT KIỆM 2 TRIỆU/THÁNG CHỈ VỚI 5 MẸO NÀY\n\nHook: Ae shipper ơi, mỗi tháng ae đang đổ bao nhiêu tiền xăng? Mình tiết kiệm được 2 triệu/tháng chỉ với 5 mẹo đơn giản này:\n\n1️⃣ Đổ xăng lúc 5-6h sáng (xăng lạnh = nhiều hơn)\n2️⃣ Giữ tốc độ 40-50km/h ổn định\n3️⃣ Tắt máy khi chờ >30 giây\n4️⃣ Kiểm tra lốp mỗi sáng (lốp non hao 15%)\n5️⃣ Đổ full bình, không đổ 50k 50k\n\nFollow @shippershop.vn để nhận thêm tips mỗi ngày!\n\n🎵 Nhạc: trending sound\n📱 App: shippershop.vn\n\n#shipper #giaohang #tietkiemxang #GHTK #GHN #meoshipper #fyp #viral"
  ],
  [
    "title" => "Thu nhập thật shipper 2026",
    "content" => "💰 THU NHẬP THẬT CỦA SHIPPER 2026 - KHÔNG NỔ, KHÔNG GIẤU\n\nHook: Nhiều người hỏi mình ship 1 tháng kiếm bao nhiêu? Đây là con số thật:\n\n📦 25-35 đơn/ngày (GHTK)\n💰 Thu: 350-500k/ngày\n⛽ Xăng: -80-100k\n🔧 Xe: -30k\n➡️ Ròng: 200-370k/ngày\n➡️ Tháng: 6-11 triệu\n\nNgày sale: 600-800k\nNgày mưa: 150-200k\n\nKhông giàu nhưng tự do. Ae thấy sao?\n\n📱 Cộng đồng shipper: shippershop.vn\n\n#shipper #thunhap #luongshipper #GHTK #GHN #2026 #fyp"
  ],
  [
    "title" => "Đồ nghề shipper PHẢI CÓ",
    "content" => "✅ 8 MÓN SHIPPER MỚI BẮT BUỘC PHẢI MUA\n\nHook: Đừng ra đường ship mà thiếu 8 món này!\n\n1. Sạc dự phòng 10000mAh (hết pin = mất đơn)\n2. Bình nước 1 lít\n3. Áo mưa BỘ (không phải cánh dơi!)\n4. Túi giữ nhiệt\n5. Dây buộc hàng\n6. Thuốc đau đầu + dầu gió\n7. Găng tay chống nắng\n8. Kính chống bụi\n\nTổng: 300k → tiết kiệm TRIỆU/tháng\n\n📱 Thêm tips: shippershop.vn\n\n#shipper #donghieshipper #newshipper #tips #fyp"
  ],
  [
    "title" => "So sánh 5 hãng ship",
    "content" => "📊 SO SÁNH 5 HÃNG SHIP - AE NÊN CHẠY HÃNG NÀO?\n\nHook: Ae mới muốn làm shipper? Đây là so sánh thật sau 2 năm chạy tất cả:\n\n🟢 GHTK: Đơn NHIỀU nhất, phí trung bình\n🟠 GHN: Phí CAO nhất, đơn ít hơn\n🔴 J&T: Đơn ít, hay đổi chính sách\n🟣 SPX: Đơn Shopee, phí THẤP nhất\n🟡 Ninja Van: Ít đơn, ít áp lực\n\nMình khuyên: GHTK hoặc GHN trước!\n\n📱 Chi tiết: shippershop.vn\n\n#shipper #GHTK #GHN #SPX #JT #sosanh #fyp"
  ],
  [
    "title" => "Cảnh báo lừa đảo shipper",
    "content" => "⚠️ CẢNH BÁO: CHIÊU LỪA ĐẢO MỚI NHẮM VÀO SHIPPER\n\nHook: Ae shipper ơi cẩn thận! Tuần rồi mình suýt mất 5 triệu vì chiêu này:\n\nKhách đặt COD 5 triệu → giao xong show MÀN HÌNH CHUYỂN KHOẢN GIẢ\n\nCách phòng:\n✅ COD >1tr → đếm tiền mặt TẠI CHỖ\n✅ KHÔNG nhận chuyển khoản\n✅ Đơn khu vắng → gọi trước\n✅ Quay video khi giao\n\nSave + share để ae biết phòng tránh!\n\n📱 Cảnh báo real-time: shippershop.vn\n\n#shipper #canhbao #luadao #safety #fyp #viral"
  ],
  [
    "title" => "Chuyện hài chỉ shipper hiểu",
    "content" => "😂 CHUYỆN CHỈ SHIPPER MỚI HIỂU\n\nHook: Tag 1 ae shipper bạn biết!\n\n1. GPS chỉ vào NGÕ CỤT 💀\n2. 'Em ở tầng 20, KHÔNG CÓ THANG MÁY'\n3. Khách ghi 'để trước cửa' - cửa NÀO?\n4. Rate 5 sao + tip 2k... cũng là tình cảm 😅\n5. 40°C, khách nhắn 'ship nhanh nha'\n6. Đang chạy 50km/h, app báo 'đơn mới cách 200m'\n\nAe có chuyện gì hài comment đi!\n\n📱 Chia sẻ thêm: shippershop.vn\n\n#shipper #funny #congdongshipper #fyp #haihuoc"
  ],
  [
    "title" => "Mùa mưa shipper phải nhớ",
    "content" => "🌧️ 5 ĐIỀU BẮT BUỘC NHỚ KHI SHIP MÙA MƯA\n\nHook: Năm ngoái 3 ae shipper bị tai nạn vì đường trơn. Đừng để xảy ra nữa!\n\n1. Bọc hàng 2 LỚP nilon (1 lớp vẫn thấm!)\n2. Áo mưa BỘ, không dùng cánh dơi\n3. Giảm tốc qua vũng nước (ổ gà ẩn!)\n4. DỪNG khi sấm sét + mưa to\n5. Kiểm tra phanh 2 lần/tuần\n\nAN TOÀN LÀ TRÊN HẾT!\n\n📱 Cảnh báo thời tiết: shippershop.vn\n\n#shipper #muamua #antoan #safety #tips #fyp"
  ],
  [
    "title" => "Giao hàng nhanh gấp đôi",
    "content" => "🚀 5 MẸO GIAO HÀNG NHANH HƠN 30%\n\nHook: Muốn giao nhiều đơn hơn mỗi ngày? 5 mẹo này giúp mình tăng từ 20 lên 30 đơn/ngày:\n\n1. Gọi khách TRƯỚC 5 PHÚT → giao 1 phút\n2. Học thuộc con hẻm → nhanh hơn GPS\n3. Chụp ảnh TRƯỚC → tránh khiếu nại\n4. Max 3-4 đơn/chuyến\n5. Sắp hàng theo thứ tự giao\n\nThử 1 ngày sẽ thấy khác biệt!\n\n📱 Tips mỗi ngày: shippershop.vn\n\n#shipper #giaohang #tips #nangcap #fyp"
  ],
  [
    "title" => "Tâm sự shipper 3 năm",
    "content" => "💪 3 NĂM LÀM SHIPPER - 7 BÀI HỌC ĐÁNG GIÁ\n\nHook: Nếu quay lại 3 năm trước, đây là những điều mình sẽ tự nhắn nhủ:\n\n1. Kiên nhẫn là kỹ năng #1\n2. Sức khỏe = vốn liếng lớn nhất\n3. TIẾT KIỆM 30% mỗi tháng\n4. Bảo dưỡng xe ĐỊNH KỲ\n5. Đừng so sánh thu nhập\n6. Lịch sự = nhiều đơn hơn\n7. Luôn có kế hoạch B\n\nGửi ae đang mệt: nghề nào cũng khó 💪\n\n📱 Cộng đồng: shippershop.vn\n\n#shipper #tamsu #motivation #3nam #fyp"
  ],
  [
    "title" => "Sửa xe cơ bản cho shipper",
    "content" => "🔧 5 LỖI XE MÁY HAY GẶP + CÁCH TỰ SỬA\n\nHook: Hỏng xe giữa đường = mất cả buổi. 30 phút học 5 cái này = tiết kiệm triệu/năm:\n\n1. Xẹp lốp → bơm mini 50k Shopee\n2. Bugi chết → bugi dự phòng 15k\n3. Xích chùng → cờ lê 14mm\n4. Phanh yếu → má phanh 30k\n5. Đèn cháy → bóng dự phòng 10k\n\nTổng đầu tư: 120k\nTiết kiệm: hàng triệu/năm!\n\n📱 Mẹo shipper: shippershop.vn\n\n#shipper #suaxe #xemay #tips #DIY #fyp"
  ],
];

$count = 0;
foreach ($scripts as $i => $s) {
    $day = intval($i / 3);
    $slot = $i % 3;
    $hours = [9, 15, 20][$slot];
    $schedTime = date('Y-m-d H:i:s', strtotime("+{$day} days {$hours}:00:00"));
    
    $exists = $d->fetchOne("SELECT id FROM content_queue WHERE title = ? AND type = 'tiktok'", [$s['title']]);
    if ($exists) { echo "⚠️ Skip (exists): {$s['title']}\n"; continue; }
    
    $d->query("INSERT INTO content_queue (type, title, content, `status`, scheduled_at) VALUES ('tiktok', ?, ?, 'pending', ?)", [
        $s['title'],
        $s['content'],
        $schedTime
    ]);
    $count++;
    echo "✅ TikTok #$count: {$s['title']}\n";
}

echo "\n🎉 Added $count TikTok scripts!\n";
echo "Total pending: " . $d->fetchOne("SELECT COUNT(*) c FROM content_queue WHERE `status`='pending'")['c'] . "\n";
