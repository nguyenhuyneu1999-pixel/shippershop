<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();
$pdo = $d->getConnection();

// 12 groups × 10 posts = 120 quality posts
$allPosts = [

// ========== GROUP 1: Shipper GHTK ==========
1 => [
  ["Kinh nghiệm 1 năm chạy GHTK full-time:\n\n✅ Đơn nhiều nhất trong các hãng - trung bình 30-40 đơn/ngày\n✅ App ổn định, ít crash hơn trước\n✅ CSKH qua chat app phản hồi nhanh\n✅ Được chọn khu vực giao\n\n❌ Phí/đơn thấp hơn GHN 3-5k\n❌ Đơn hoàn không tính công\n❌ Hệ thống điểm phạt strict\n❌ COD đối soát T+3\n\nAe GHTK đồng ý không?","review"],
  ["GHTK vừa điều chỉnh phụ phí xăng dầu từ 12/03/2026.\n\nPhí giảm nhẹ so với tháng trước. Ae check app cập nhật nhé.\n\nTiết kiệm xăng:\n1. Tốc độ ổn định 40-50km/h\n2. Tắt máy khi chờ >30s\n3. Kiểm tra lốp mỗi sáng\n4. Plan route trước khi chạy\n\nPhí mới ảnh hưởng nhiều không ae?","discussion"],
  ["Hỏi ae GHTK: khu nào HCM đông đơn nhất?\n\nMình ship khu Bình Thạnh, ngày chỉ 20-25 đơn. Ae ship khu nào 35+ đơn/ngày chỉ giúp!\n\nĐang tính chuyển khu vực.","question"],
  ["Tips nhận đơn GHTK hiệu quả:\n\n1. Bật app sớm 7h sáng - đơn sáng nhiều\n2. Ở gần bưu cục - nhận đơn nhanh hơn\n3. Giao nhanh 3-4 đơn đầu → hệ thống ưu tiên\n4. Giờ cao điểm 11-13h + 17-19h: ON liên tục\n5. Không từ chối đơn liên tiếp → giảm ưu tiên\n\nAe có mẹo gì thêm?","tips"],
  ["Bảng lương GHTK 2026 thực tế:\n\n📦 Nội thành HCM: 15-25k/đơn\n📦 Ngoại thành: 20-35k/đơn\n📦 Đơn cồng kềnh: +5-10k\n📦 Thưởng KPI tháng: 500k-2tr\n\nThu nhập ròng: 6-12 triệu/tháng\n\nAe đang nhận bao nhiêu/đơn? Share để so sánh!","discussion"],
  ["⚠️ GHTK đổi chính sách đơn hoàn từ tháng 3:\n\nTrước: hoàn miễn phí 2 lần\nSau: phí hoàn từ lần 2\n\nAe cẩn thận gọi xác nhận khách trước khi giao. Đơn COD gọi trước 100%.\n\nAi có thông tin chi tiết hơn share giúp!","warning"],
  ["So sánh app GHTK vs GHN (góc nhìn shipper):\n\n📱 GHTK:\n- Scan nhanh, ít lag\n- Bản đồ integrate tốt\n- Thông báo đơn real-time\n\n📱 GHN:\n- UI đẹp hơn\n- Tracking chi tiết hơn\n- Thống kê thu nhập rõ ràng\n\nAe thích app nào hơn?","review"],
  ["Mẹo giao đơn GHTK nhanh cho ae mới:\n\n1. Sắp hàng theo route TRƯỚC khi chạy\n2. Đơn gần giao trước, xa giao sau\n3. Gọi khách trước 5 phút\n4. Chụp ảnh giao hàng LUÔN\n5. COD đếm tiền tại chỗ\n6. Đơn hoàn → mang lại kho NGAY\n\nTiết kiệm 1-2 tiếng/ngày!","tips"],
  ["Confession GHTK: Hôm nay giao 42 đơn, kỷ lục cá nhân! 🎉\n\nBắt đầu 7h sáng, kết thúc 8h tối. Xăng hết 95k, thu 520k gross.\n\nBí quyết: plan route kỹ + gọi khách trước + không nghỉ trưa quá 20 phút.\n\nAe kỷ lục bao nhiêu đơn/ngày?","fun"],
  ["Ae GHTK ơi, bảo hiểm tai nạn GHTK cover những gì?\n\nMình chạy 1 năm rồi mà không rõ. Hôm trước bị va quệt nhẹ, may không sao.\n\nAe nào đã claim bảo hiểm GHTK chia sẻ kinh nghiệm với!","question"],
],

// ========== GROUP 2: Shipper Grab - Be - Gojek ==========
2 => [
  ["So sánh thu nhập GrabFood vs BeFood vs GoFood 2026:\n\n🟢 GrabFood: 12-20k/đơn, đơn nhiều nhất, chờ nhà hàng lâu\n🟡 BeFood: 15-22k/đơn, đơn ít hơn, app hay lag\n🟠 GoFood: 14-25k/đơn, thưởng ngày tốt, đơn trung bình\n\nMình chạy GrabFood là chính vì đơn đều.\nAe chạy app nào? 🍕","review"],
  ["Tips tăng thu nhập GrabFood:\n\n1. Chạy giờ cao điểm 11-13h + 18-21h\n2. Ở gần khu nhiều nhà hàng (quận 1, 3, 7 HCM)\n3. Accept đơn nhanh - rating cao = đơn nhiều\n4. Đơn xa phí cao - đừng reject hết\n5. Surge pricing khi mưa = x1.5-2\n\nMùa mưa = cơ hội kiếm nhiều!","tips"],
  ["⚠️ Ae Grab cẩn thận: chiêu lừa mới!\n\nKhách book GrabBike, giữa đường bảo 'ghé mua đồ' → chạy mất.\n\nCách phòng:\n✅ Không dừng khu vắng\n✅ Đơn booking lạ → chat xác nhận\n✅ Quay dashcam/camera\n\nAe gặp chuyện gì share để cảnh báo!","warning"],
  ["Grab vừa update chính sách thưởng tháng 3:\n\n📊 Quest mới: 50 đơn/tuần = +200k\n📊 Rating >4.8 = ưu tiên đơn\n📊 Giờ cao điểm bonus +3k/đơn\n\nAe thấy quest mới có khó hơn không?\nMình thấy target tăng mà thưởng giữ nguyên 😅","discussion"],
  ["Hỏi ae Be: app Be có ổn hơn trước không?\n\nMình nghe nói Be cải thiện nhiều. Đang chạy Grab nhưng muốn thêm 1 app phụ.\n\nBe có đơn đều không? Phí ship ra sao?\n\nAe đang chạy Be cho ý kiến!","question"],
  ["Ngày mưa - ngày kiếm tiền shipper Grab 🌧️💰\n\nHôm qua mưa to 3 tiếng, chạy GrabFood:\n- Surge x1.8 liên tục\n- 18 đơn trong 3 tiếng\n- Thu: 380k (3 tiếng!)\n\nBí quyết: áo mưa tốt + bọc hàng 2 lớp + chạy chậm an toàn.\n\nMưa = vàng cho shipper! Ae đồng ý không? 😂","discussion"],
  ["Review túi giao đồ ăn cho ae Grab/Be:\n\n🎒 Túi Grab chính hãng: bền, to, nhưng nặng\n🎒 Túi generic Shopee: rẻ 80k, nhưng mau hỏng\n🎒 Túi giữ nhiệt loại dày: 150k, giữ nóng/lạnh tốt\n\nMình recommend: mua túi giữ nhiệt 150k + túi Grab backup.\n\nAe dùng túi gì?","review"],
  ["Chuyện hài Grab: Hôm nay giao trà sữa lên tầng 15 không thang máy 😂\n\nĐi bộ 15 tầng, đến nơi mồ hôi nhễ nhại. Khách mở cửa: 'Anh ơi trà sữa có bị đổ không?'\n\nMay là không đổ. Tip 5k 😂\n\nAe có chuyện gì hài share đi!","fun"],
  ["Be vs Grab: ai trả phí tốt hơn cho driver?\n\nSau 3 tháng chạy cả 2:\n\n🟢 Grab: đơn nhiều hơn, phí TB 15k, thưởng quest\n🟡 Be: đơn ít hơn 40%, phí TB 18k, ít thưởng\n\nTổng thu: Grab > Be (vì đơn nhiều hơn)\nNhưng phí/đơn: Be > Grab\n\nAe chạy cả 2 thấy sao?","review"],
  ["Tips bảo vệ rating Grab 4.8+:\n\n1. Chào khách: 'Dạ em giao hàng cho anh/chị'\n2. Đồ ăn: giữ thẳng, không nghiêng\n3. Gọi trước 2 phút khi đến\n4. Đợi tối đa 5 phút (đừng hủy vội)\n5. Cảm ơn sau khi giao\n\nRating cao = đơn nhiều = thu nhập cao. Đầu tư 5 giây lịch sự!","tips"],
],

// ========== GROUP 3: Shipper Sài Gòn ==========
3 => [
  ["🗺️ Map khu vực đông đơn Sài Gòn (kinh nghiệm 2 năm):\n\n🔥 Q1: đơn nhiều nhất nhưng kẹt xe + khó đậu\n🔥 Q3: đơn đều, đường dễ đi\n🔥 Q7 (Phú Mỹ Hưng): đơn giá trị cao\n🔥 Tân Bình: đơn cực nhiều, đường nhỏ\n🔥 Bình Thạnh: trung bình, route dễ\n\nAe ship khu nào? Share tips khu vực!","tips"],
  ["⚠️ Update ngập nước HCM mùa mưa 2026:\n\n📍 Nguyễn Hữu Cảnh: ngập nặng sau mưa 30p\n📍 Võ Văn Ngân (Thủ Đức): ngập thường xuyên\n📍 Tô Ngọc Vân: mưa to = ngập 30cm\n📍 An Dương Vương (Q5): ngập khu cầu chữ Y\n\nAe tránh mấy tuyến này khi mưa. Đi đường nào thay thế?","warning"],
  ["Tips ship khu Gò Vấp - Bình Thạnh:\n\n📍 Ngã tư Hàng Xanh: kẹt 7-9h + 17-19h → Nguyễn Xí\n📍 Phan Văn Trị: hay ngập mùa mưa\n📍 Cityland: bảo vệ OK, gọi cư dân xuống\n📍 Bạch Đằng: khó đậu, ship giờ trưa\n📍 Chợ Bà Chiểu: đông buổi sáng\n\nAe khu khác share!","tips"],
  ["Kẹt xe Sài Gòn - 5 tuyến tránh giờ cao điểm:\n\n🚫 Điện Biên Phủ (7-9h, 17-19h)\n🚫 Nam Kỳ Khởi Nghĩa (17-19h)\n🚫 Cầu Sài Gòn (cả ngày!)\n🚫 Nguyễn Thị Thập Q7 (17-19h)\n🚫 Trường Chinh (7-9h)\n\nĐường thay thế: Bạch Đằng, Xô Viết Nghệ Tĩnh, Lê Văn Lương.\n\nAe biết đường tắt nào share!","tips"],
  ["Quán ăn ngon + rẻ cho shipper Sài Gòn:\n\n🍚 Cơm tấm Bà Loan (Q.Bình Thạnh): 25k, no\n🍜 Phở Bà Sáu (Gò Vấp): 30k, ngon\n🍚 Cơm bụi Chú Tám (Tân Bình): 20k!\n🥤 Trà đá miễn phí: quán cóc đường Nguyễn Trãi\n\nNghỉ trưa ở đâu cho mát? Mình hay đậu dưới gầm cầu vượt 😅\n\nAe share quán quen!","fun"],
  ["Thu nhập shipper Sài Gòn theo khu vực:\n\n💰 Q1-3: 400-600k/ngày (đơn nhiều, phí cao)\n💰 Q7: 350-500k (đơn ít, giá trị cao)\n💰 Tân Bình-Tân Phú: 300-450k (đơn rất nhiều)\n💰 Q12-Hóc Môn: 250-350k (xa, đơn ít)\n💰 Thủ Đức: 300-400k (đang phát triển)\n\nAe đang ship khu nào?","discussion"],
  ["Hỏi ae SG: ship khu Thủ Đức có đông đơn không?\n\nMình đang Q.Bình Thạnh, nghe nói Thủ Đức đang phát triển. Nhiều chung cư mới = nhiều đơn?\n\nAe đang ship Thủ Đức cho ý kiến!","question"],
  ["Mùa mưa SG shipper cần chuẩn bị:\n\n✅ Áo mưa BỘ (không cánh dơi)\n✅ Bọc điện thoại chống nước\n✅ Nilon bọc hàng (mua cuộn ở chợ Kim Biên)\n✅ Giày/dép chống trượt\n✅ Khăn lau khô dự phòng\n\nMưa SG đến nhanh, hết nhanh. Chờ 20-30 phút thường hết. Đừng liều!","tips"],
  ["Confession: Sài Gòn 36°C, ship từ 7h-20h, về nhà muốn xỉu 😅\n\nNhưng check ví: 480k. Thôi mai chạy tiếp.\n\nAe SG hôm nay kiếm được bao nhiêu?","fun"],
  ["Trạm xăng rẻ nhất Sài Gòn cho shipper:\n\n⛽ Petrolimex Cộng Hòa (Tân Bình): giá chuẩn + vắng\n⛽ PVOil Lê Văn Việt (Q9): rẻ hơn 200-500đ/lít\n⛽ Trạm nhỏ khu Gò Vấp: hay khuyến mãi\n\nĐổ đầy bình lúc 5-6h sáng = vắng + xăng lạnh.\n\nAe biết trạm nào rẻ share!","tips"],
],

// ========== GROUP 4: Shipper Hà Nội ==========
4 => [
  ["🗺️ Khu vực đông đơn Hà Nội:\n\n🔥 Cầu Giấy - Mỹ Đình: chung cư + văn phòng\n🔥 Hoàn Kiếm: đơn nhiều nhưng cấm xe máy nhiều tuyến\n🔥 Hai Bà Trưng: đơn đều, đường OK\n🔥 Thanh Xuân: khu dân cư đông\n🔥 Long Biên: đang phát triển, ít shipper\n\nAe HN ship khu nào?","tips"],
  ["⚠️ Mùa đông HN shipper cần lưu ý:\n\n🧥 Mặc ấm nhưng gọn (áo khoác gió, không parka)\n🧤 Găng tay giữ ấm + cầm lái được\n👓 Kính chống sương mù buổi sáng\n💡 Bật đèn sớm - 5h chiều đã tối\n☕ Giữ bình nước ấm\n\nNhiệt độ <10°C: cẩn thận đường trơn sương!","warning"],
  ["Tips ship khu Hoàn Kiếm - phố cổ HN:\n\n📍 Nhiều tuyến cấm xe máy → check bảng\n📍 Đường 1 chiều khắp nơi → thuộc đường\n📍 Không có chỗ đậu → gọi khách ra lấy\n📍 Weekend walking street → đi vòng\n📍 Quán Thánh, Phan Đình Phùng: đẹp nhưng khó ship\n\nAe ship phố cổ share mẹo!","tips"],
  ["Thu nhập shipper Hà Nội 2026:\n\n💰 Hoàn Kiếm: 350-500k/ngày\n💰 Cầu Giấy: 300-450k\n💰 Thanh Xuân: 280-400k\n💰 Long Biên: 250-350k\n💰 Gia Lâm: 200-300k\n\nMùa đông: giảm 20-30% (ít đơn hơn)\nMùa hè: tăng 10-20% (đồ uống nhiều)\n\nAe đang kiếm bao nhiêu?","discussion"],
  ["Kẹt xe Hà Nội - tuyến né:\n\n🚫 Phạm Hùng (7-9h, 17-19h)\n🚫 Nguyễn Trãi - Ngã Tư Sở (cả ngày)\n🚫 Cầu Giấy → Xuân Thủy (17-19h)\n🚫 Kim Mã → Liễu Giai (7-9h)\n🚫 Minh Khai - Trường Chinh (17-19h)\n\nĐường tắt: Trần Thái Tông, Dương Đình Nghệ, ngõ Thái Hà.\n\nAe share đường tắt!","tips"],
  ["Quán ăn shipper HN hay ghé:\n\n🍚 Bún chả Hương Liên (Lê Văn Hưu): 35k\n🍜 Phở Thìn Bờ Hồ: 40k (đắt nhưng ngon)\n🍚 Cơm rang Chú Bảy (Cầu Giấy): 25k\n🥤 Trà đá: 3k khắp nơi 😂\n\nAe hay ăn ở đâu?","fun"],
  ["Mưa rào HN mùa hè - kinh nghiệm ship:\n\n1. Check dự báo trước khi ra đường\n2. Mưa HN thường 30-60 phút rồi tạnh\n3. Trú ở gầm cầu vượt hoặc trạm xe bus\n4. Khu hay ngập: Phan Bội Châu, Lý Thường Kiệt\n5. Sau mưa: đường trơn 30 phút đầu\n\nAn toàn là trên hết!","tips"],
  ["Ship khu Mỹ Đình - Cầu Giấy tips:\n\n📍 Chung cư nhiều: thuộc số tòa + cửa vào\n📍 Keangnam: bảo vệ strict, gọi khách xuống\n📍 The Manor: vào cổng bên, không cổng chính\n📍 ĐH Quốc Gia: sinh viên đặt nhiều 11-13h\n📍 Indochina Plaza: cần gửi xe tầng hầm\n\nAe thêm info!","tips"],
  ["Ae HN: chạy GHTK hay GHN ở Hà Nội tốt hơn?\n\nMình nghe nói GHN ở HN đơn nhiều hơn SG. Ae đang chạy ở HN confirm giúp!\n\nĐang cân nhắc đăng ký thêm 1 hãng.","question"],
  ["Tết xong ae HN chạy lại chưa?\n\nMình nghỉ Tết 10 ngày, giờ chạy lại thấy đơn ít hơn trước Tết. Chắc mọi người còn đang holiday mood.\n\nAe thấy đơn thế nào? Khi nào mới đông lại?","discussion"],
],

// ========== GROUP 5: Review Đồ Ship ==========
5 => [
  ["Review túi giữ nhiệt cho shipper (đã dùng 3 loại):\n\n⭐⭐⭐⭐⭐ Túi dày 2 lớp (150k Shopee): giữ nóng 2h, lạnh 3h, BỀN\n⭐⭐⭐ Túi Grab chính hãng: to, nặng, giữ nhiệt TB\n⭐⭐ Túi rẻ 50k: mau hỏng sau 2 tháng, không giữ nhiệt\n\nRecommend: mua loại 150k. Đầu tư 1 lần dùng 1 năm.\n\nAe dùng loại nào?","review"],
  ["TOP 5 sạc dự phòng tốt nhất cho shipper 2026:\n\n1. Anker PowerCore 10000 (350k): nhẹ, sạc nhanh, bền\n2. Xiaomi Redmi 20000 (250k): dung lượng lớn, nặng\n3. Samsung 10000 (400k): chất lượng cao\n4. ROMOSS 10000 (150k): rẻ, OK cho 1 ngày\n5. Baseus 20000 (300k): balance tốt\n\nMình dùng Anker - 6 tháng vẫn tốt.\nAe dùng sạc gì?","review"],
  ["So sánh áo mưa cho shipper:\n\n🌧️ Áo mưa BỘ Rando (200k): chống nước tốt nhất, bền\n🌧️ Áo bộ Sơn Thủy (120k): OK, hơi nóng\n🌧️ Áo cánh dơi: RẺ nhưng KHÔNG nên dùng (ướt hàng = đền)\n🌧️ Áo Poncho: che được hàng nhưng nguy hiểm\n\nĐầu tư 200k áo tốt = không bao giờ lo ướt hàng.\n\nAe dùng áo nào?","review"],
  ["Review bơm mini cho shipper (xẹp lốp giữa đường):\n\n✅ Bơm điện mini Xiaomi (180k): tự động, nhanh, nhỏ gọn\n✅ Bơm tay nhỏ (50k): rẻ, nhẹ, phải bơm tay\n❌ Bình xịt bơm lốp (30k): tạm bợ, không đáng\n\nMình dùng bơm Xiaomi - 2 phút bơm đầy lốp. Worth it!\n\nAe có recommendation gì?","review"],
  ["Kính chống nắng/bụi cho shipper:\n\n😎 Kính UV400 (50-80k): chống nắng tốt, nhẹ\n😎 Kính phân cực (150k): giảm chói, lái xe tốt\n😎 Kính đổi màu (200k): tự tối khi nắng\n😎 Kính trong suốt (30k): chống bụi, đi tối\n\nMình mua 2 cái: phân cực (ban ngày) + trong suốt (ban đêm).\n\nAe dùng gì?","review"],
  ["Điện thoại nào tốt cho shipper 2026?\n\n📱 Budget (3-5tr): Samsung A15, Redmi Note 13\n📱 Mid (5-8tr): Samsung A55, Xiaomi 14T\n📱 Cần: GPS tốt, pin lớn (5000mAh+), màn sáng\n\nĐỪNG mua: iPhone SE (pin yếu), điện thoại RAM <4GB\n\nMình dùng Samsung A55 - pin trâu, GPS chuẩn.\nAe dùng gì?","review"],
  ["Review giá đỡ điện thoại xe máy:\n\n⭐⭐⭐⭐⭐ Kẹp 4 góc xoay 360° (80k): chắc, xoay được\n⭐⭐⭐ Kẹp từ tính (150k): tiện nhưng rớt khi xóc\n⭐⭐ Túi gắn ghi đông (50k): rẻ, che nắng, touchscreen kém\n\nRecommend: kẹp 4 góc 80k. Chắc chắn nhất.\n\nAe dùng loại nào?","review"],
  ["Găng tay shipper - loại nào tốt?\n\n🧤 Găng chống nắng dài (40k): mỏng, mát, chống UV\n🧤 Găng da (100k): bền, ấm, nhưng nóng mùa hè\n🧤 Găng touch screen (60k): bấm điện thoại được!\n🧤 Găng cao su (20k): chống mưa, nóng\n\nMùa hè: chống nắng + touch screen\nMùa đông: da hoặc giữ ấm\n\nAe mix match thế nào?","review"],
  ["Cốp xe máy cho shipper - nên hay không?\n\n✅ Cốp to 45L (500k): chứa nhiều, bảo vệ hàng\n✅ Thùng nhựa (200k): rẻ, gắn baga\n❌ Ship không cốp: hàng dễ rơi, ướt mưa\n\nMình gắn cốp 45L - giao 3-4 đơn 1 lúc EZ. Đầu tư 1 lần!\n\nAe dùng cốp hay thùng?","review"],
  ["TOP 3 app phụ trợ shipper nên cài:\n\n📱 Google Maps: bản đồ chính, route tốt\n📱 ShipperShop: cảnh báo giao thông từ ae shipper\n📱 WearFit: theo dõi sức khỏe (nhịp tim, bước chân)\n\nBonus: CamScanner (chụp biên nhận), Splitwise (chia tiền)\n\nAe cài app gì hay?","tips"],
],

// ========== GROUP 6: Confession Shipper ==========
6 => [
  ["Confession: Hôm nay giao đơn cho bạn gái cũ. Cô ấy không nhận ra mình.\n\nMỉm cười chào 'Dạ em giao hàng cho chị', rồi đi.\n\n3 năm rồi. Đời shipper là vậy, cứ đi tiếp.\n\n#confession","discussion"],
  ["Thú nhận: Mình chạy ship 3 năm rồi bố mẹ vẫn nghĩ mình làm 'nhân viên logistics'. Không dám nói ship vì sợ bố mẹ buồn.\n\nNhưng thu nhập 10-12 triệu/tháng, tự lo được cuộc sống. Có gì phải xấu hổ đâu?\n\nAe có ai giấu gia đình không?","discussion"],
  ["Confession: Hôm nay giao đơn cho 1 bà cụ sống 1 mình. Bà mua mì gói + thuốc.\n\nGiao xong bà cầm tay cảm ơn, mắt rưng rưng. Mình nghĩ: có khi đơn hàng này là cả ngày bà chờ đợi.\n\nNghề ship đôi khi không chỉ là giao hàng. 🥺","discussion"],
  ["Thú nhận: Suýt bỏ nghề 5 lần rồi 😅\n\n1. Mưa to, ướt hết hàng, khách chửi\n2. Bị cướp điện thoại khu vắng\n3. Xe hỏng giữa đường 3 lần liên tiếp\n4. Tháng 1 kiếm chỉ 4 triệu (dịch)\n5. Tai nạn nhẹ, nằm viện 1 tuần\n\nNhưng vẫn ở đây. Vì biết làm gì khác? 💪","discussion"],
  ["Confession: Mình là shipper nữ. Chạy GHTK 2 năm.\n\nKhó khăn:\n- Hàng nặng phải tự bê\n- Đi tối 1 mình đáng sợ\n- Khách đôi khi coi thường\n\nNhưng:\n+ Tự do, không ai quản\n+ Thu nhập 8-10 triệu\n+ Quen biết nhiều\n\nAe nữ nào đang ship? 💪","discussion"],
  ["Thú nhận: Ship 2000 đơn/tháng, 6 tháng liền. Nhận badge Top Shipper.\n\nNhưng cái giá: đau lưng, ít gặp gia đình, bạn bè không còn rủ đi chơi.\n\nĐời shipper: nhiều tiền hơn nhưng ít thời gian hơn. Trade-off.\n\nAe nghĩ thế nào?","discussion"],
  ["Confession: Khách tip 100k cho đơn giao dưới mưa bão 🥹\n\nĐơn giá 50k, ship phí 15k. Mình giao tận cửa, bọc 3 lớp. Khách mở ra: 'Em đợi anh chút.'\n\nQuay lại đưa 100k: 'Anh cảm ơn, trời mưa thế này mà vẫn giao.'\n\nNhỏ thôi nhưng nhớ mãi. 🙏","fun"],
  ["Thú nhận: Mình từ kỹ sư IT chuyển sang ship.\n\nLương IT: 15 triệu, ngồi 8h, stress\nLương ship: 10-12 triệu, tự do, khỏe\n\nMọi người bảo mình điên. Nhưng 1 năm qua mình hạnh phúc hơn nhiều.\n\nAe có ai chuyển nghề sang ship không?","discussion"],
  ["Confession: Hôm nay là sinh nhật mình. Không ai chúc. Ngồi 1 mình ăn cơm tấm lề đường.\n\nĐời shipper: đông người nhưng cô đơn.\n\nNhưng thôi, mai lại chạy tiếp. Happy birthday to me 🎂","discussion"],
  ["Thú nhận: Mình bỏ rượu bia từ khi làm shipper.\n\nTrước đây nhậu 4-5 lần/tuần. Giờ: 5h dậy, chạy ship, 9h tối về ngủ.\n\nSức khỏe tốt hơn, tiết kiệm 3 triệu/tháng tiền nhậu, đầu óc minh mẫn.\n\nNghề ship thay đổi cuộc đời mình theo cách không ngờ! 💪","discussion"],
],

// ========== GROUP 7: Mẹo Tiết Kiệm Xăng ==========
7 => [
  ["5 mẹo tiết kiệm 2 triệu xăng/tháng:\n\n1. Đổ xăng 5-6h sáng (lạnh = nhiều hơn)\n2. Tốc độ ổn định 40-50km/h\n3. Tắt máy chờ >30 giây\n4. Kiểm tra lốp mỗi sáng (non = +15% xăng)\n5. Đổ full bình, không 50k 50k\n\nMình từ 3tr xăng → 1.5tr/tháng!\nAe áp dụng chưa?","tips"],
  ["Xăng Ron 95 vs Ron 92 cho xe số shipper:\n\n⛽ Ron 92: rẻ hơn 1000đ/lít, đủ cho xe số\n⛽ Ron 95: đắt hơn, chỉ cần cho xe ga/phun xăng điện tử\n\nXe Wave, Dream, Sirius → Ron 92 là đủ!\nXe Air Blade, Vision → Ron 95 tốt hơn\n\nĐổ đúng loại = tiết kiệm 500-1000đ/lít. Tháng = 300-500k!\n\nAe đổ Ron mấy?","tips"],
  ["Route tối ưu = tiết kiệm xăng:\n\n1. Plan route TRƯỚC khi chạy\n2. Gom đơn cùng khu vực\n3. Đi đường chính, tránh đường nhỏ quanh co\n4. Tránh giờ cao điểm (idle = hao xăng)\n5. Dùng Google Maps 'tránh kẹt xe'\n\nMình tiết kiệm 15-20km/ngày = 500-700đ xăng = 15-20k/tháng","tips"],
  ["Bảo dưỡng xe = tiết kiệm xăng:\n\n🔧 Thay nhớt đúng hạn (1500km): -10% xăng\n🔧 Vệ sinh bugi: -5% xăng\n🔧 Kiểm tra xích/sên: -5% xăng\n🔧 Căn chỉnh bộ chế hòa khí (carb): -15% xăng!\n\nTổng: xe bảo dưỡng đúng = tiết kiệm 30-35% xăng so với xe lâu không sửa.\n\nĐầu tư 200k/tháng bảo dưỡng = tiết kiệm 600k xăng!","tips"],
  ["Hỏi ae: xe nào tiết kiệm xăng nhất cho ship?\n\n🏍️ Honda Wave Alpha: 1.5L/100km (champion!)\n🏍️ Honda Dream: 1.7L/100km\n🏍️ Yamaha Sirius: 1.8L/100km\n🏍️ Honda Air Blade: 2.2L/100km\n🏍️ Honda Vision: 2.5L/100km\n\nXe số LUÔN tiết kiệm hơn xe ga.\n\nAe đang đi xe gì?","question"],
  ["Xe điện cho shipper - có đáng không?\n\n⚡ Ưu điểm:\n- Điện rẻ hơn xăng 70%\n- Không bảo dưỡng động cơ\n- Êm, ít ồn\n\n⚡ Nhược điểm:\n- Pin 60-80km/lần sạc (chỉ đủ nửa ngày)\n- Trạm sạc ít\n- Giá xe cao (30-50 triệu)\n\nChưa phù hợp cho shipper full-time. Nhưng 2-3 năm nữa?\n\nAe nghĩ sao?","discussion"],
  ["Trick: Đổ xăng đúng cách tiết kiệm 10%:\n\n1. Đổ SÁNG SỚM (5-7h) - xăng lạnh, đậm đặc hơn\n2. Đổ CHẬM - không bóp nhanh (bọt khí)\n3. Đổ FULL - không để gần cạn (cặn bẩn hại máy)\n4. Đổ ở trạm UY TÍN - trạm vắng hay pha xăng\n5. Không đổ khi xe bồn đang rót (xáo trộn cặn)\n\nNhỏ nhưng tích lũy!","tips"],
  ["Giá xăng tháng 3/2026 - ảnh hưởng thu nhập shipper:\n\nRon 92: ~22.000đ/lít\nRon 95: ~23.000đ/lít\n\nShipper trung bình: 3-4 lít/ngày = 66-88k\nTháng: 2-2.6 triệu xăng\n\nChiếm 20-25% thu nhập! Nên tiết kiệm xăng = tăng thu nhập ròng.\n\nAe hao bao nhiêu/ngày?","discussion"],
  ["Test thực tế: Giảm tốc từ 60km/h xuống 45km/h - kết quả:\n\n🏍️ Xe: Wave Alpha 2019\n📏 Quãng đường: 50km/ngày, 7 ngày mỗi tốc độ\n\nTốc 60km/h: 2.1L/ngày = 46k\nTốc 45km/h: 1.5L/ngày = 33k\n\nTiết kiệm: 13k/ngày = 390k/tháng!\n\nChậm hơn 5 phút/chuyến nhưng tiết kiệm gần 400k/tháng. Worth it!","tips"],
  ["Lốp xe ảnh hưởng xăng nhiều hơn ae nghĩ:\n\n🔴 Lốp non: +15-20% xăng (và nguy hiểm!)\n🟡 Lốp mòn: +5-10% xăng + dễ trượt\n🟢 Lốp mới, đúng áp: tiết kiệm tối đa\n\nÁp suất chuẩn: 28-32 PSI (kiểm tra mỗi sáng)\n\nMua đồng hồ đo áp lốp 30k Shopee. Best investment!","tips"],
],

// ========== GROUP 8: Shipper J&T SPX Ninja Van ==========
8 => [
  ["So sánh J&T vs SPX vs Ninja Van 2026:\n\n🔴 J&T: phí 15-25k, đơn ít, chính sách hay đổi\n🟣 SPX: phí 12-22k, đơn nhiều (Shopee), KPI cao\n🟡 Ninja Van: phí 15-20k, đơn ít nhất, ít áp lực\n\nNếu muốn đơn nhiều → SPX\nNếu muốn thoải mái → Ninja Van\n\nAe đang chạy hãng nào?","review"],
  ["Tips ship đơn Shopee (SPX) hiệu quả:\n\n1. Nhận đơn từ kho sáng sớm (10h kho đông)\n2. Đơn freeship khách hay cancel → giao nhanh\n3. COD: đối soát chậm 3-5 ngày → chuẩn bị vốn\n4. Đơn hoàn: mang lại kho NGAY\n5. Scan mã nhanh = nhiều đơn hơn\n\nAe SPX bổ sung!","tips"],
  ["J&T thay đổi chính sách gì tháng 3/2026?\n\nMình nghe:\n- Phí COD mới\n- Thưởng KPI điều chỉnh\n- Khu vực giao mở rộng\n\nAe nào có info chính xác share giúp!\nĐang phân vân có nên đăng ký lại J&T không.","question"],
  ["Ninja Van: ưu và nhược điểm thật sự:\n\n✅ Ưu: ít áp lực KPI, thoải mái giờ giấc, phí OK\n❌ Nhược: đơn ÍT, không có khu vực riêng, app basic\n\nPhù hợp: ae muốn ship phụ, không phải thu nhập chính.\n\nAe Ninja Van đồng ý?","review"],
  ["SPX shipper - mẹo đạt KPI:\n\n📊 Target: 25-30 đơn/ngày\n\n1. Đến kho sớm 8h - chọn đơn gần\n2. Gọi khách trước 10 phút\n3. Giao nhanh 3 đơn đầu → hệ thống ưu tiên\n4. Không giữ đơn quá 2 giờ\n5. Đơn failed → report ngay, không để cuối ngày\n\nAe share tips KPI!","tips"],
  ["Hỏi: J&T có bảo hiểm cho shipper không?\n\nMình đăng ký J&T 3 tháng, chưa thấy thông tin bảo hiểm.\n\nSPX thì có gói cơ bản. Ninja Van thì mình không rõ.\n\nAe nào biết info share!","question"],
  ["SPX peak season (sale Shopee) - kinh nghiệm sống sót:\n\n📦 Sale 3.3, 4.4, 6.6...: đơn x2-3\n📦 Kho quá tải → đợi lâu\n📦 Khách order nhiều → cancel cũng nhiều\n\nMẹo:\n1. Đến kho 7h sáng\n2. Gom max đơn 1 chuyến\n3. Không nhận quá 40 đơn/ngày\n4. Ăn no trước khi chạy 😅\n\nSale = tiền nhiều nhưng mệt x3!","tips"],
  ["Chuyển từ SPX sang J&T - nên không?\n\nMình chạy SPX 6 tháng:\n- Đơn nhiều nhưng phí thấp (12-18k)\n- KPI áp lực\n- COD chậm\n\nJ&T: phí cao hơn 20%, đơn ít hơn 50%\n\nTính toán: SPX 30 đơn × 15k = 450k vs J&T 18 đơn × 22k = 396k\n\nSPX vẫn hơn! Ae tính sao?","discussion"],
  ["App J&T mới update - ae thấy sao?\n\nUI mới:\n✅ Đẹp hơn, scan nhanh hơn\n✅ Bản đồ integrate tốt\n❌ Hay lag khi load đơn\n❌ Thông báo đơn đôi khi chậm\n\nAe dùng thấy OK chưa?","review"],
  ["Ninja Van tuyển shipper mới - chia sẻ kinh nghiệm đăng ký:\n\n1. Đăng ký online: app Ninja Van Driver\n2. Cần: CMND + GPLX + xe máy\n3. Training: 1 buổi online\n4. Bắt đầu: ngay sau training\n\nDễ hơn GHTK/GHN. Nhưng đơn ít → chỉ nên làm phụ.\n\nAe mới muốn thử?","tips"],
],

// ========== GROUP 9: Tips Giao Hàng Nhanh ==========
9 => [
  ["5 mẹo giao hàng nhanh hơn 30%:\n\n1. GỌI KHÁCH TRƯỚC 5 PHÚT → giao 1 phút\n2. Học thuộc hẻm khu hay giao → nhanh hơn GPS\n3. Chụp ảnh hàng TRƯỚC → tránh khiếu nại\n4. Max 3-4 đơn/chuyến = tối ưu route\n5. Sắp hàng theo thứ tự giao\n\nTăng từ 20 lên 30 đơn/ngày!","tips"],
  ["Route planning cho shipper:\n\n📱 Google Maps: bật 'tránh kẹt xe'\n📱 Gom đơn cùng hướng\n📱 Giao đơn gần trước, xa sau\n📱 Đơn COD giao giờ trưa (khách hay ở nhà)\n📱 Đơn ship express giao trước\n\nRoute tốt = ít km = ít xăng = nhiều đơn hơn!","tips"],
  ["Scan đơn nhanh - tiết kiệm 30 phút/ngày:\n\n1. Sắp đơn theo SỐ trước khi đi\n2. Dán sticker số thứ tự lên đơn\n3. Mã vạch hướng lên → scan 1 chạm\n4. Đơn cùng tòa nhà gom chung\n5. Kiểm tra đủ đơn TRƯỚC khi rời kho\n\n30 phút/ngày × 30 ngày = 15 tiếng/tháng!\n\nAe có mẹo gì?","tips"],
  ["Giao đơn chung cư hiệu quả:\n\n🏢 Gọi khách trước khi vào\n🏢 Hỏi số tòa + tầng CHÍNH XÁC\n🏢 Gom tất cả đơn cùng tòa\n🏢 Giao từ tầng cao xuống thấp\n🏢 Bảo vệ: nói rõ giao hàng, biển số xe\n\nNếu thang máy hỏng → gọi khách xuống. Đừng leo 15 tầng!","tips"],
  ["Xử lý đơn failed nhanh:\n\n📞 Khách không nghe máy:\n→ Gọi 3 lần, cách nhau 5 phút\n→ Nhắn SMS/Zalo: 'Anh/chị em giao hàng'\n→ Đợi 15 phút tại chỗ\n→ Report failed trên app\n\nĐỪNG giữ đơn → phạt nặng hơn report failed!","tips"],
  ["Giao đơn COD an toàn:\n\n💰 Đơn <500k: nhận tiền, kiểm đếm\n💰 Đơn 500k-2tr: đếm kỹ, kiểm tờ giả\n💰 Đơn >2tr: đếm 2 lần + quay video\n\n⚠️ KHÔNG nhận chuyển khoản!\n⚠️ KHÔNG giao khu vắng ban đêm (đơn lớn)\n⚠️ Mang tiền nhỏ để thối\n\nAn toàn > tốc độ!","tips"],
  ["Giao hàng dễ vỡ - cách ship không vỡ:\n\n📦 Kiểm tra đóng gói khi nhận từ kho\n📦 Thêm giấy/bọt biển nếu thiếu\n📦 Để đứng, không nằm\n📦 Tách riêng khỏi đơn khác\n📦 Đi chậm qua ổ gà\n📦 Giao tận tay, không để cổng\n\nHàng vỡ = mình chịu trách nhiệm. Cẩn thận!","tips"],
  ["Giao đồ ăn không bị đổ:\n\n🍜 Túi giữ nhiệt: BẮT BUỘC\n🍜 Để thẳng đứng, không nghiêng\n🍜 Tách đồ nước vs đồ khô\n🍜 Đi chậm qua gờ giảm tốc\n🍜 Không phanh gấp\n🍜 Giao nhanh (đồ ăn nguội = rate xấu)\n\nĐồ ăn: thời gian là vàng!","tips"],
  ["Xử lý khách khó tính:\n\n😤 Khách chửi: bình tĩnh, 'dạ em xin lỗi'\n😤 Khách đòi kiểm hàng: cho kiểm, chụp ảnh\n😤 Khách cancel lúc đến: report, giữ bằng chứng\n😤 Khách quỵt COD: KHÔNG giao nếu nghi ngờ\n\nBình tĩnh = professional. Mất 1 đơn ≠ mất nghề.","tips"],
  ["Thời gian giao tối ưu theo loại đơn:\n\n⏰ Đồ ăn: 15-25 phút (càng nhanh càng tốt)\n⏰ Đơn thường: 2-4 giờ (linh hoạt)\n⏰ Đơn express: 1-2 giờ (ưu tiên)\n⏰ Đơn sáng: giao trước 12h\n⏰ Đơn chiều: giao trước 18h\n\nGiao đúng giờ = rating cao = đơn nhiều!","tips"],
],

// ========== GROUP 10: Hỏi Đáp Shipper ==========
10 => [
  ["FAQ shipper mới (phần 1):\n\nQ: Cần gì để đăng ký?\nA: CMND/CCCD + GPLX + xe máy + smartphone\n\nQ: Hãng nào nên đăng ký đầu tiên?\nA: GHTK hoặc GHN (đơn nhiều, dễ bắt đầu)\n\nQ: Thu nhập bao nhiêu?\nA: 6-12 triệu/tháng (full-time)\n\nAe hỏi thêm gì?","tips"],
  ["Hỏi: Shipper có cần đóng thuế không?\n\nTheo mình hiểu:\n- Thu nhập <100 triệu/năm: không cần\n- Thu nhập >100 triệu/năm: đóng thuế TNCN\n- Một số hãng đã trừ thuế trước\n\nAe nào rõ luật thuế cho shipper share giúp!","question"],
  ["Hỏi đáp: Bị tai nạn khi giao hàng - quyền lợi gì?\n\n1. Bảo hiểm hãng (nếu có): claim theo quy định\n2. Bảo hiểm xe máy bắt buộc: cover tai nạn\n3. Hãng ship: có trách nhiệm nếu có HĐ lao động\n\n⚠️ Lưu ý: đa số shipper là 'đối tác', KHÔNG phải nhân viên → quyền lợi hạn chế.\n\nAe nên mua BH tai nạn cá nhân ~500k/năm.","tips"],
  ["Hỏi: Ship part-time có đáng không?\n\nKinh nghiệm mình (part-time 4h/ngày chiều):\n- Thu nhập: 3-5 triệu/tháng\n- Đơn: 10-15 đơn/ngày\n- Phù hợp: sinh viên, người có việc khác\n\nKhó khăn: ít đơn giờ thấp điểm, phí/đơn thấp hơn\n\nAe part-time share kinh nghiệm!","question"],
  ["Hỏi: Shipper nên mở tài khoản ngân hàng nào?\n\n🏦 Vietcombank: phí COD thấp, ít lỗi\n🏦 Techcombank: 0 phí chuyển khoản\n🏦 MB Bank: app tốt, 0 phí\n🏦 TPBank: ATM 24/7, 0 phí\n\nPhần lớn hãng ship đối soát qua VCB hoặc Techcom.\n\nAe dùng ngân hàng nào?","question"],
  ["Hỏi: Xe máy cũ có ship được không?\n\nĐược! Điều kiện:\n✅ Xe chạy ổn, phanh tốt\n✅ Có biển số + đăng ký\n✅ Đèn + còi hoạt động\n\nNhưng nên:\n🔧 Bảo dưỡng trước khi bắt đầu\n🔧 Kiểm tra lốp + nhớt + xích\n🔧 Có tiền dự phòng sửa xe (~500k)\n\nXe cũ OK, miễn chạy ổn!","tips"],
  ["Hỏi: Có nên chạy ship đêm?\n\nƯu điểm:\n+ Đường vắng, giao nhanh\n+ Phụ phí đêm (một số hãng)\n\nNhược điểm:\n- Nguy hiểm hơn (tầm nhìn, cướp)\n- Ít đơn hơn ban ngày\n- Mệt, ảnh hưởng sức khỏe\n\nNên ship đêm nếu: khu an toàn + quen đường + cần thêm thu nhập.\n\nAe ship đêm share!","discussion"],
  ["Hỏi: Làm sao report khách hàng xấu?\n\n1. Chụp ảnh bằng chứng\n2. Ghi nhận thời gian + địa chỉ\n3. Report trên app (mục Hỗ trợ)\n4. Gọi hotline hãng\n5. Ghi note cho đơn sau cùng địa chỉ\n\nĐỪNG tự xử - để hãng handle.\nAe gặp khách xấu thế nào?","tips"],
  ["Hỏi: Shipper có nên mua bảo hiểm y tế?\n\nCÓ! Lý do:\n- Ship = nguy cơ tai nạn cao\n- Nắng mưa = dễ bệnh\n- Chi phí bệnh viện ĐÁNG SỢ\n\nGói recommend:\n- BHYT nhà nước: 800k/năm (cover 80%)\n- BH tai nạn: 500k/năm\n- BH sức khỏe toàn diện: 2-3tr/năm\n\nĐầu tư sức khỏe = đầu tư nghề nghiệp!","tips"],
  ["Hỏi: Shipper nghỉ phép thế nào?\n\nVì shipper là 'đối tác' nên:\n- Không có nghỉ phép có lương\n- Nghỉ = không thu nhập\n- Nghỉ lâu = mất ưu tiên đơn\n\nMẹo:\n✅ Tiết kiệm 1 tháng lương dự phòng\n✅ Nghỉ max 3-5 ngày\n✅ Báo trước cho bưu cục\n\nAe nghỉ thế nào?","discussion"],
],

// ========== GROUP 11: Shipper Đà Nẵng ==========
11 => [
  ["🗺️ Khu vực đông đơn Đà Nẵng:\n\n🔥 Hải Châu: trung tâm, đơn nhiều nhất\n🔥 Thanh Khê: dân cư đông, đơn đều\n🔥 Sơn Trà: du lịch, đơn đồ ăn nhiều\n🔥 Ngũ Hành Sơn: ít hơn, nhưng phí cao\n🔥 Liên Chiểu: khu CN, đơn ít\n\nAe ĐN ship khu nào?","tips"],
  ["Tips ship Đà Nẵng mùa bão:\n\n⛈️ Theo dõi dự báo HÀNG NGÀY\n⛈️ Bão cấp 6+: NGHỈ, không ship\n⛈️ Sau bão: đường ngập, cây đổ → đi chậm\n⛈️ Cất xe trong nhà khi bão\n⛈️ Sạc đầy pin điện thoại + sạc dự phòng\n\nAn toàn là trên hết ae ĐN! 🙏","warning"],
  ["Thu nhập shipper Đà Nẵng 2026:\n\n💰 Hải Châu: 250-400k/ngày\n💰 Thanh Khê: 200-350k\n💰 Sơn Trà: 200-350k\n\nThấp hơn SG/HN ~20% nhưng chi phí sinh hoạt cũng thấp hơn.\n\nAe ĐN đang kiếm bao nhiêu?","discussion"],
  ["Đà Nẵng ship hãng nào tốt nhất?\n\nMình thấy:\n- GHTK: đơn nhiều nhất ở ĐN\n- GHN: phí tốt, đơn khá\n- J&T: đơn ít\n- Grab: đồ ăn nhiều khu du lịch\n\nAe ĐN confirm giúp!","question"],
  ["Cầu Rồng phun lửa cuối tuần - ae ship tránh nhé! 🐉\n\nThứ 7 + CN: 21h phun lửa → kẹt từ 20h30\nTuyến tránh: cầu Sông Hàn hoặc cầu Trần Thị Lý\n\nDu lịch đông = đơn đồ ăn nhiều nhưng kẹt xe cũng nhiều 😅\n\nAe ĐN share tips!","tips"],
  ["Quán ăn ngon rẻ cho shipper Đà Nẵng:\n\n🍜 Mì Quảng Bà Mua (Thanh Khê): 25k, ngon\n🍚 Cơm gà Thùy (Hải Châu): 30k\n🥤 Chè bắp chợ Cồn: 10k!\n🍜 Bún mắm nêm: 20k khắp nơi\n\nĐà Nẵng ăn rẻ + ngon hơn SG/HN nhiều 😊\n\nAe hay ăn ở đâu?","fun"],
  ["Ship khu Sơn Trà - tips cho ae mới:\n\n📍 Đường dốc nhiều → cẩn thận hàng dễ vỡ\n📍 Resort + khách sạn: gọi lễ tân trước\n📍 Bán đảo: GPS đôi khi sai\n📍 Khu du lịch: đơn đồ ăn nhiều buổi tối\n📍 Mùa du lịch (5-8): đông đúc\n\nAe Sơn Trà share!","tips"],
  ["Ae ĐN: có ai chạy Be/Grab ở đây không?\n\nMình thấy Grab ĐN đơn ít hơn SG nhiều. Be thì gần như không có.\n\nAe nào chạy app ride-hailing ở ĐN cho review!","question"],
  ["Mùa du lịch Đà Nẵng = cơ hội shipper:\n\n📦 Đồ ăn delivery tăng 50%\n📦 Khách du lịch order Shopee giao tại khách sạn\n📦 Phí ship cao hơn (khu du lịch)\n\nMẹo: chạy khu Sơn Trà + Ngũ Hành Sơn mùa hè!\n\nAe ĐN confirm?","tips"],
  ["Thời tiết Đà Nẵng và lịch ship:\n\n☀️ Mùa khô (3-8): ship thoải mái\n🌧️ Mùa mưa (9-12): mưa dầm, đường ngập\n⛈️ Mùa bão (9-11): nghỉ khi bão\n\nLịch ship tốt nhất: 3-8 (mùa du lịch + khô ráo)\n\nAe ĐN thấy đúng không?","discussion"],
],

// ========== GROUP 12: Đồ Nghề Shipper ==========
12 => [
  ["Bộ đồ nghề shipper cơ bản (tổng 500k):\n\n✅ Sạc dự phòng 10000mAh: 200k\n✅ Túi giữ nhiệt: 150k\n✅ Áo mưa bộ: 120k\n✅ Dây buộc hàng: 20k\n✅ Bơm mini: 50k\n✅ Kính UV: 30k\n\nTổng: ~500k. Đầu tư 1 lần, dùng 1 năm!\n\nAe cần gì thêm?","tips"],
  ["Review balo shipper (đã dùng 4 loại):\n\n⭐⭐⭐⭐⭐ Balo giữ nhiệt 2 ngăn (250k): BEST\n⭐⭐⭐⭐ Balo Grab chính hãng (0 - được phát)\n⭐⭐⭐ Balo generic Shopee (100k): OK 3 tháng\n⭐⭐ Balo du lịch: không phù hợp, không giữ nhiệt\n\nRecommend: mua balo giữ nhiệt 2 ngăn. Ngăn nóng + ngăn lạnh.\n\nAe dùng loại nào?","review"],
  ["Bán: Túi giữ nhiệt 35L, dùng 2 tháng, còn mới 90%.\nGiá: 100k (mua mới 180k).\n\nLý do bán: đổi sang cốp xe.\n\nAe HCM inbox mình!","discussion"],
  ["So sánh cốp xe vs thùng gắn baga:\n\n📦 Cốp xe 45L (500k):\n+ Gọn, đẹp, chống mưa\n+ Chứa 3-4 đơn\n- Đắt, khó tháo\n\n📦 Thùng nhựa gắn baga (150k):\n+ Rẻ, to hơn\n+ Dễ tháo/lắp\n- Xấu, không chống mưa\n\nMình dùng cốp 45L - đáng đầu tư!\n\nAe team nào?","review"],
  ["Giá đỡ điện thoại nào chắc nhất?\n\n🔩 Kẹp 4 góc xoay (80k): ⭐⭐⭐⭐⭐ - chắc nhất\n🧲 Kẹp từ tính (150k): ⭐⭐⭐ - rớt khi xóc\n📱 Túi gắn ghi đông (50k): ⭐⭐ - touch kém\n🔧 Kẹp RAM (300k): ⭐⭐⭐⭐⭐ - siêu chắc, đắt\n\nMình dùng kẹp 4 góc 80k. 6 tháng chưa rớt lần nào!\n\nAe recommend gì?","review"],
  ["Đèn pin/đèn LED cho ship đêm:\n\n💡 Đèn pha LED xe (gắn thêm): 200k, sáng vượt trội\n💡 Đèn pin đội đầu: 80k, tiện tay\n💡 Đèn chiếu cốp: 50k, soi hàng trong cốp\n\nShip đêm BẮT BUỘC có đèn tốt. Tầm nhìn = an toàn.\n\nAe ship đêm dùng đèn gì?","review"],
  ["Camera hành trình cho shipper - cần không?\n\n📹 Camera mini gắn xe (300-500k):\n+ Bằng chứng tai nạn\n+ Chống khách quỵt\n+ An toàn khu vắng\n\n- Pin phải sạc\n- Rung = video mờ\n\nAe đang cân nhắc mua. Ai dùng rồi review!","question"],
  ["Mua đồ nghề shipper ở đâu rẻ nhất?\n\n🛒 Shopee: giá rẻ nhất, ship lâu\n🛒 Lazada: giá OK, ship nhanh hơn\n🛒 Chợ Kim Biên (HCM): nilon, dây buộc, rẻ sỉ\n🛒 Chợ Đồng Xuân (HN): đồ nghề, giá sỉ\n🛒 Tiệm xe máy: phụ tùng, giá chuẩn\n\nTip: mua combo trên Shopee thường rẻ hơn lẻ 30%.\n\nAe hay mua ở đâu?","tips"],
  ["DIY: Tự làm giá đỡ hàng từ ống nhựa PVC:\n\n🔧 Vật liệu: ống PVC 21mm + keo + dây rút\n🔧 Tổng chi phí: 50k\n🔧 Thời gian: 30 phút\n\nGắn vào baga xe → chở được thùng to, không rơi.\n\nAe nào muốn hướng dẫn chi tiết comment!","tips"],
  ["Ắc quy sạc dự phòng cho shipper:\n\n🔋 Loại 12V mini (500k): sạc xe máy khi hết bình\n🔋 Jump starter (800k): khởi động xe chết máy\n\nĐầu tư hơi đắt nhưng 1 lần xe chết máy giữa đường = mất cả buổi.\n\nAe có ai dùng jump starter chưa?","question"],
],

];

// Insert posts
$stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content, images, type, likes_count, comments_count, shares_count, status, created_at) VALUES (?, ?, ?, ?, 'post', ?, ?, ?, 'active', ?)");

$userIds = range(3, 102);
$totalInserted = 0;

foreach ($allPosts as $gid => $posts) {
    shuffle($userIds);
    $gCount = 0;
    foreach ($posts as $i => $p) {
        $content = $p[0];
        $uid = $userIds[$i % count($userIds)];
        $imgNum = (($gid * 10 + $i) % 20) + 1;
        $seedNum = (($gid * 10 + $i) % 30) + 1;
        
        // Mix real + seed images
        $img = ($i % 3 == 0) ? "[\"/uploads/posts/real/real_{$imgNum}.jpg\"]" : "[\"/uploads/posts/seed_v2_{$seedNum}.jpg\"]";
        
        $likes = rand(15, 120);
        $cmts = rand(5, 35);
        $shares = rand(2, 15);
        $daysAgo = rand(0, 6);
        $hoursAgo = rand(0, 23);
        $cat = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours"));
        
        // Check duplicate
        $exists = $pdo->prepare("SELECT id FROM group_posts WHERE group_id = ? AND content = ? LIMIT 1");
        $exists->execute([$gid, $content]);
        if ($exists->fetch()) continue;
        
        try {
            $stmt->execute([$gid, $uid, $content, $img, $likes, $cmts, $shares, $cat]);
            $gCount++;
            $totalInserted++;
        } catch (Exception $e) {
            echo "  ❌ G#$gid: " . $e->getMessage() . "\n";
        }
    }
    echo "✅ Group #$gid: +$gCount posts\n";
}

echo "\n🎉 Inserted $totalInserted quality posts across 12 groups!\n";

// Update group post counts
$pdo->exec("UPDATE `groups` g SET post_count = (SELECT COUNT(*) FROM group_posts gp WHERE gp.group_id = g.id AND gp.status = 'active')");
echo "✅ Updated group post counts\n";

// Show final counts
$stats = $d->fetchAll("SELECT g.id, g.name, COUNT(gp.id) as cnt FROM `groups` g LEFT JOIN group_posts gp ON g.id = gp.group_id AND gp.status='active' GROUP BY g.id ORDER BY g.id");
echo "\n📊 Final post counts:\n";
foreach ($stats as $s) echo "  #{$s['id']} {$s['name']}: {$s['cnt']} posts\n";
