<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$d = db();
$batch = intval($_GET['batch'] ?? 1);

$provinces = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Bình Dương','Đồng Nai','Hải Phòng','Cần Thơ','Nghệ An','Thanh Hóa','Lào Cai'];
$districts_hn = ['Cầu Giấy','Đống Đa','Ba Đình','Hoàn Kiếm','Thanh Xuân','Hai Bà Trưng','Long Biên','Bắc Từ Liêm','Hà Đông','Tây Hồ'];
$districts_hcm = ['Quận 1','Quận 3','Quận 7','Quận 10','Bình Thạnh','Gò Vấp','Tân Bình','Phú Nhuận','Thủ Đức','Quận 12'];
$types = ['post','tip','question','review'];

// 100 posts data: [content, image_files, type, province_idx, likes, comments]
$posts = [];

// ===== BATCH 1: Posts 1-25 (Road/Traffic + Rain) =====
if ($batch == 1) { $posts = [
['Ae ơi đường Nguyễn Trãi hôm nay kẹt cứng luôn, ai đi hướng Hà Đông nên tránh nhé. Mình đứng đây 30 phút rồi chưa nhúc nhích', '["\/uploads\/posts\/theme_road_0.jpg"]', 'post', 0, rand(5,45), rand(2,12)],
['Mẹo cho ae mới: Nên cài Google Maps chạy nền, nó báo kẹt xe sớm hơn app hãng nhiều. Hôm qua nhờ vậy mà mình tránh được đoạn Trường Chinh, tiết kiệm 20 phút', '["\/uploads\/posts\/theme_road_1.jpg"]', 'tip', 0, rand(20,80), rand(5,20)],
['Sáng nay ship đơn Quận 7, đường đẹp gió mát lắm ae. Chạy ven sông thấy cuộc sống shipper cũng vui', '["\/uploads\/posts\/theme_road_2.jpg"]', 'post', 1, rand(10,50), rand(3,15)],
['Có ai biết đường tắt từ Cầu Giấy qua Thanh Xuân mà ít kẹt không ạ? Chiều nào cũng bị dính ở Ngã Tư Sở', '["\/uploads\/posts\/theme_bridge_0.jpg"]', 'question', 0, rand(8,30), rand(5,18)],
['Cầu Thủ Thiêm 2 ban đêm đẹp phết ae. Ship đơn khuya mà chạy qua đây thấy Sài Gòn lung linh quá', '["\/uploads\/posts\/theme_bridge_1.jpg"]', 'post', 1, rand(15,60), rand(3,10)],
['Cập nhật: Đường Phạm Văn Đồng đoạn gần sân bay đang sửa, ae đi hướng khác nhé. Dự kiến sửa đến cuối tháng', '["\/uploads\/posts\/theme_road_3.jpg"]', 'post', 1, rand(12,40), rand(4,15)],
['Mưa to quá ae ơi, ship đơn COD 5 triệu mà run tay luôn. May mà có áo mưa xịn', '["\/uploads\/posts\/theme_rain_0.jpg"]', 'post', 0, rand(20,70), rand(8,25)],
['Mẹo đi mưa cho ae: Bọc điện thoại vô túi zip, gắn lên ghi đông. Vừa xem map vừa không sợ ướt. Mình làm 2 năm nay chưa hỏng con nào', '["\/uploads\/posts\/theme_rain_1.jpg"]', 'tip', 1, rand(30,100), rand(10,30)],
['Sài Gòn 4h chiều lại mưa rồi ae ơi. Đơn dồn hết giờ cao điểm, ai muốn kiếm thêm thì online đi nha', '["\/uploads\/posts\/theme_rain_2.jpg"]', 'post', 1, rand(10,35), rand(3,12)],
['Hà Nội mưa phùn gió bấc, ship bún bò cho chị khách mà thấy thương luôn. Chị ấy tip thêm 20k bảo "em cố gắng nhé"', '["\/uploads\/posts\/theme_rain_0.jpg","\/uploads\/posts\/theme_food_1.jpg"]', 'post', 0, rand(25,90), rand(8,25)],
['Review đường Vành đai 3 đoạn mới thông xe: Rộng, đẹp, ít xe. Ae chạy GHTK hướng Hoài Đức nên đi đường này', '["\/uploads\/posts\/theme_road_0.jpg","\/uploads\/posts\/theme_road_1.jpg"]', 'review', 0, rand(15,50), rand(5,15)],
['Chạy Grab hôm nay 15 cuốc, 480k. Ngày mưa đơn nhiều nhưng cực hơn bình thường gấp đôi', '["\/uploads\/posts\/theme_rain_1.jpg"]', 'post', 1, rand(18,55), rand(7,20)],
['Ae cẩn thận đoạn Âu Cơ gần chợ Nhật Tảo, sáng nay có tai nạn nhỏ, đường hẹp kẹt lắm', '["\/uploads\/posts\/theme_road_2.jpg"]', 'post', 1, rand(8,30), rand(4,12)],
['Mẹo tiết kiệm xăng: Giữ tốc độ 40-45km/h ổn định, không tăng giảm ga đột ngột. Mình áp dụng tiết kiệm được 15-20% xăng/tháng', '["\/uploads\/posts\/seed_post_0.jpg"]', 'tip', 0, rand(35,120), rand(12,35)],
['Đà Nẵng sáng nay trời đẹp quá ae. Giao hàng ven biển mát rượi, khác hẳn mấy ngày trước nắng chói', '["\/uploads\/posts\/theme_bridge_2.jpg"]', 'post', 2, rand(12,40), rand(3,10)],
['Hỏi ae: Ship đơn ngoại thành Bình Dương có xa lắm không? Mình mới chuyển từ GHN sang SPX, chưa quen địa bàn', '["\/uploads\/posts\/seed_post_1.jpg"]', 'question', 3, rand(5,25), rand(8,20)],
['1 ngày của shipper Hà Nội: 5h dậy, 6h online, 12h nghỉ trưa, 13h chạy tiếp, 20h về nhà. Vất vả nhưng tự do ae ạ', '["\/uploads\/posts\/theme_sunset_0.jpg"]', 'post', 0, rand(30,100), rand(10,30)],
['Đường về quê ship hàng liên tỉnh. Chạy xe máy 50km giao 1 đơn, tiền ship 35k nhưng được ngắm cảnh đẹp thế này thì cũng OK', '["\/uploads\/posts\/theme_field_0.jpg"]', 'post', 7, rand(20,60), rand(5,15)],
['Ae GHTK ơi, hôm nay app lỗi gì mà đơn nào cũng báo sai địa chỉ vậy? Có ai bị giống mình không?', '["\/uploads\/posts\/seed_post_2.jpg"]', 'question', 0, rand(15,50), rand(12,30)],
['Cánh đồng lúa chín vàng ven đường ship hàng Long An. Chạy ngang thấy đẹp quá phải dừng lại chụp 1 tấm', '["\/uploads\/posts\/theme_field_1.jpg","\/uploads\/posts\/theme_field_2.jpg"]', 'post', 1, rand(25,80), rand(5,15)],
['Mẹo giao hàng giờ cao điểm Sài Gòn: Đi đường nhỏ trong hẻm, tuy xa hơn chút nhưng không bị kẹt. Kinh nghiệm 3 năm ae ạ', '["\/uploads\/posts\/theme_road_3.jpg"]', 'tip', 1, rand(28,90), rand(8,22)],
['Bị kẹt xe 45 phút trên cầu Sài Gòn, đơn bị trễ mà khách còn gọi điện chửi. Buồn quá ae ơi', '["\/uploads\/posts\/theme_bridge_0.jpg"]', 'post', 1, rand(35,110), rand(15,40)],
['Hoàng hôn Hà Nội nhìn từ cầu Nhật Tân. Ship đơn cuối ngày mà gặp cảnh này thì mệt mấy cũng quên', '["\/uploads\/posts\/theme_sunset_1.jpg"]', 'post', 0, rand(20,70), rand(5,15)],
['Có ai ship khu vực Hải Phòng không? Mình mới chuyển ra đây, muốn hỏi thăm ae về địa bàn', '["\/uploads\/posts\/seed_post_3.jpg"]', 'question', 5, rand(5,20), rand(6,18)],
['Cuối ngày tổng kết: 28 đơn, thu nhập 650k sau trừ xăng. Ngày không tệ ae nhỉ?', '["\/uploads\/posts\/theme_sunset_2.jpg"]', 'post', 1, rand(15,45), rand(5,15)],
]; }

// ===== BATCH 2: Posts 26-50 (Food/Coffee + Package) =====
if ($batch == 2) { $posts = [
['Ship trà sữa cho khách mà thèm quá, cuối cùng mua luôn 1 ly. Shipper cũng cần thưởng cho mình ae nhỉ', '["\/uploads\/posts\/theme_food_0.jpg"]', 'post', 1, rand(20,65), rand(5,15)],
['Review quán cà phê ven đường Lê Văn Sỹ: Giá rẻ, có wifi, có ổ cắm sạc pin. Ae shipper ghé nghỉ chân tiện lắm', '["\/uploads\/posts\/theme_coffee_0.jpg"]', 'review', 1, rand(25,80), rand(8,25)],
['Giao đơn cơm trưa xong ghé quán quen uống ly cà phê đá. 15k mà ngồi nghỉ 15 phút, sạc pin điện thoại, lên tinh thần chiều chạy tiếp', '["\/uploads\/posts\/theme_coffee_1.jpg"]', 'post', 0, rand(18,55), rand(4,12)],
['Mẹo: Mua bình giữ nhiệt loại 500ml, pha cà phê ở nhà mang theo. Tiết kiệm 20-30k/ngày, 1 tháng = 600k-900k ae ạ', '["\/uploads\/posts\/theme_coffee_2.jpg"]', 'tip', 0, rand(40,130), rand(12,35)],
['Ship đồ ăn đêm Sài Gòn toàn gặp mấy quán ngon. Tối qua giao bún riêu cua mà nước dùng thơm lừng, nhịn không nổi phải ghé mua 1 tô', '["\/uploads\/posts\/theme_food_1.jpg"]', 'post', 1, rand(22,70), rand(6,18)],
['Ae cẩn thận khi giao đồ ăn: Để túi thẳng đứng, không nghiêng. Hôm qua mình bị đổ canh ra túi, phải đền khách 50k', '["\/uploads\/posts\/theme_food_2.jpg","\/uploads\/posts\/theme_food_0.jpg"]', 'tip', 1, rand(30,95), rand(10,28)],
['Kiện hàng to quá ae ơi! Đơn này 20kg mà ship bằng xe máy. May có dây chằng không thì rơi dọc đường', '["\/uploads\/posts\/theme_package_0.jpg"]', 'post', 0, rand(15,50), rand(7,20)],
['Mẹo đóng gói hàng dễ vỡ: Bọc bubble wrap 3 lớp, cho vào hộp cứng, chèn giấy báo xung quanh. 2 năm ship chưa bể đơn nào', '["\/uploads\/posts\/theme_package_1.jpg"]', 'tip', 1, rand(35,110), rand(10,30)],
['Giao xong đơn COD 8 triệu, khách kiểm hàng 20 phút mới nhận. Đứng chờ mà hồi hộp luôn ae', '["\/uploads\/posts\/theme_package_2.jpg"]', 'post', 0, rand(12,40), rand(5,15)],
['Ai hay ship ở khu chợ Bến Thành không? Đông người, kẹt xe, khó tìm địa chỉ. Mình bị lạc hoài', '["\/uploads\/posts\/theme_market_0.jpg"]', 'question', 1, rand(8,30), rand(8,22)],
['Ship hàng chợ Đồng Xuân sáng sớm. 5h sáng mà đông nghẹt người, xe tải, xe ba gác chạy loạn xạ', '["\/uploads\/posts\/theme_market_1.jpg"]', 'post', 0, rand(14,45), rand(4,12)],
['Review túi giữ nhiệt mua trên Shopee 89k: Dùng được 3 tháng, khá ổn cho giá tiền. Giữ nhiệt khoảng 1.5 tiếng', '["\/uploads\/posts\/theme_package_0.jpg","\/uploads\/posts\/theme_helmet_0.jpg"]', 'review', 1, rand(20,65), rand(6,18)],
['Giao hàng vào chợ Tân Bình lúc 10h sáng, đông khách mua sắm lắm. Phải dắt xe đi bộ vào trong', '["\/uploads\/posts\/theme_market_2.jpg"]', 'post', 1, rand(10,35), rand(3,10)],
['Đơn hàng siêu dài: Ship từ Quận 1 lên Củ Chi, 40km. Cước 55k, xăng hết 30k. Lãi 25k nhưng chạy 1.5 tiếng ae ạ', '["\/uploads\/posts\/seed_post_4.jpg"]', 'post', 1, rand(18,55), rand(8,22)],
['Cà phê sáng trước giờ online. Thói quen 2 năm nay của mình: 1 ly đen đá, ngồi plan tuyến đường, rồi mới bật app', '["\/uploads\/posts\/theme_coffee_0.jpg","\/uploads\/posts\/theme_coffee_1.jpg"]', 'post', 0, rand(22,70), rand(5,15)],
['Ship mỹ phẩm cho chị khách, chị ấy tặng mình mấy sample kem chống nắng. Shipper chạy ngoài đường cần lắm ae', '["\/uploads\/posts\/seed_post_5.jpg"]', 'post', 1, rand(25,80), rand(6,18)],
['Ae J&T có biết bưu cục Quận 10 chuyển đi đâu không? Hôm qua mình chạy đến mà thấy đóng cửa', '["\/uploads\/posts\/seed_post_6.jpg"]', 'question', 1, rand(5,20), rand(8,22)],
['Cơm trưa shipper: Cơm bình dân 30k, ngồi quán vỉa hè, ae ngồi chung chia sẻ kinh nghiệm. Vui lắm', '["\/uploads\/posts\/theme_food_0.jpg","\/uploads\/posts\/theme_food_2.jpg"]', 'post', 0, rand(28,90), rand(8,22)],
['Mẹo xử lý đơn khách hẹn giao lại: Gọi trước 30 phút, nhắn tin xác nhận. Giảm tỷ lệ bom hàng từ 15% xuống 3%', '["\/uploads\/posts\/seed_post_7.jpg"]', 'tip', 1, rand(40,130), rand(12,35)],
['Kiện hàng "Dễ vỡ" nhưng shop đóng gói bằng 1 lớp giấy mỏng. Đến nơi bể luôn, ai chịu đây ae?', '["\/uploads\/posts\/theme_package_1.jpg","\/uploads\/posts\/theme_package_2.jpg"]', 'post', 0, rand(35,110), rand(15,40)],
['Ship 3 đơn trà sữa cùng lúc, 1 đổ trong túi. Phải bỏ tiền túi mua lại cho khách. Bài học: Không nhận quá 2 đơn nước cùng lúc', '["\/uploads\/posts\/theme_food_1.jpg"]', 'post', 1, rand(20,65), rand(8,22)],
['Quán cà phê ven hồ Tây, ngồi nhìn hoàng hôn chờ đơn. Lúc nào cũng là khoảnh khắc yên bình nhất trong ngày', '["\/uploads\/posts\/theme_sunset_0.jpg","\/uploads\/posts\/theme_coffee_2.jpg"]', 'post', 0, rand(30,95), rand(5,15)],
['Hôm nay là ngày ship hàng may mắn: 35 đơn, 0 đơn bom, 3 khách tip. Tổng thu 820k ae ơi', '["\/uploads\/posts\/theme_sunset_1.jpg"]', 'post', 1, rand(25,80), rand(8,22)],
['Ship hàng khu công nghiệp Bình Dương, bảo vệ không cho vào. Phải gọi khách ra cổng nhận. Mất 15 phút chờ', '["\/uploads\/posts\/seed_post_8.jpg"]', 'post', 3, rand(10,35), rand(5,15)],
['Ae nào có kinh nghiệm ship hàng đông lạnh chia sẻ với. Mình mới nhận ship đơn hải sản mà không biết giữ lạnh thế nào', '["\/uploads\/posts\/theme_food_2.jpg"]', 'question', 1, rand(8,28), rand(10,25)],
]; }

// ===== BATCH 3: Posts 51-75 (Gear/Life + Seed images) =====
if ($batch == 3) { $posts = [
['Review mũ bảo hiểm fullface AGV giá 350k: Nhẹ, thông gió tốt, có kính chống UV. Dùng 6 tháng vẫn bền ae', '["\/uploads\/posts\/theme_helmet_0.jpg"]', 'review', 1, rand(25,80), rand(8,22)],
['Ae shipper nên đầu tư mũ fullface xịn. An toàn là trên hết, nhất là chạy đêm khuya nhiều xe tải', '["\/uploads\/posts\/theme_helmet_1.jpg"]', 'tip', 0, rand(30,95), rand(8,22)],
['Bộ đồ nghề shipper của mình: Mũ fullface, áo mưa bộ, túi giữ nhiệt, sạc dự phòng 20000mAh, găng tay', '["\/uploads\/posts\/theme_helmet_2.jpg","\/uploads\/posts\/theme_helmet_0.jpg"]', 'post', 1, rand(35,110), rand(10,28)],
['Đổ xăng hết 80k cho Honda Wave, chạy được khoảng 180km. Ae nào biết xe nào tiết kiệm xăng hơn không?', '["\/uploads\/posts\/seed_post_9.jpg"]', 'question', 0, rand(12,40), rand(10,25)],
['3 năm làm shipper, từ Wave cũ nay lên SH. Cố gắng ae ạ, nghề này vất vả nhưng có tương lai', '["\/uploads\/posts\/seed_post_10.jpg"]', 'post', 1, rand(40,130), rand(12,35)],
['Hôm nay off, ngồi nhà sửa xe cho ngày mai chạy. Thay nhớt, vá lốp, lau xe. Shipper mà xe hỏng là mất tiền ae', '["\/uploads\/posts\/seed_post_11.jpg"]', 'post', 0, rand(15,50), rand(4,12)],
['Tình huống dở khóc dở cười: Giao nhầm đơn cho 2 khách. May mà 2 nhà gần nhau, chạy đổi lại kịp', '["\/uploads\/posts\/seed_post_12.jpg"]', 'post', 1, rand(28,90), rand(10,28)],
['Ae ơi có nên đăng ký Grab hay Be không? Mình đang chạy GHTK, muốn chạy thêm app nữa tăng thu nhập', '["\/uploads\/posts\/seed_post_13.jpg"]', 'question', 0, rand(10,35), rand(12,30)],
['Chia sẻ thu nhập tháng 2/2026: GHTK 8tr, Grab 4tr, Be 2tr. Tổng 14tr, trừ xăng còn 11tr. Đủ sống ae', '["\/uploads\/posts\/seed_v2_0.jpg"]', 'post', 0, rand(45,150), rand(15,40)],
['Mẹo sạc pin điện thoại: Mua sạc nhanh 65W, 20 phút từ 10% lên 80%. Shipper cần điện thoại sống thì mới có đơn', '["\/uploads\/posts\/seed_v2_1.jpg"]', 'tip', 1, rand(30,95), rand(8,22)],
['Ship hàng cho 1 anh già, anh ấy kể ngày xưa cũng làm shipper bằng xe đạp. Thế hệ trước vất vả hơn mình nhiều ae', '["\/uploads\/posts\/seed_v2_2.jpg"]', 'post', 0, rand(35,110), rand(8,22)],
['Bị chó rượt 2 lần trong 1 ngày ở khu Thủ Đức. Ae có mẹo gì chống chó không?', '["\/uploads\/posts\/seed_v2_3.jpg"]', 'question', 1, rand(20,65), rand(15,35)],
['Hẻm nhỏ Sài Gòn, GPS bảo đến nơi nhưng không thấy số nhà. Gọi khách 5 lần không nghe máy. Đơn COD 2 triệu nữa', '["\/uploads\/posts\/seed_v2_4.jpg"]', 'post', 1, rand(22,70), rand(8,22)],
['Mẹo: Cài app đo quãng đường, cuối tháng tính xăng chính xác. Biết được ngày nào chạy hiệu quả nhất', '["\/uploads\/posts\/seed_v2_5.jpg"]', 'tip', 0, rand(25,80), rand(6,18)],
['Con gái cũng làm shipper được ae. Mình 48kg mà ship đơn 15kg vẫn OK. Quan trọng là biết cách sắp xếp', '["\/uploads\/posts\/seed_v2_6.jpg"]', 'post', 1, rand(50,160), rand(15,40)],
['Ship đơn lên Tam Đảo cho khách du lịch. Đường đèo đẹp nhưng cua tay áo nhiều, ae cẩn thận', '["\/uploads\/posts\/theme_field_0.jpg","\/uploads\/posts\/theme_field_1.jpg"]', 'post', 0, rand(18,55), rand(4,12)],
['Xe bị thủng lốp giữa đường, may có bác sửa xe ven đường vá cho 20k. Cảm ơn mấy bác thợ sửa xe, cứu shipper ae', '["\/uploads\/posts\/seed_v2_7.jpg"]', 'post', 0, rand(20,65), rand(6,18)],
['Hỏi ae: Nên mua bảo hiểm tai nạn giao thông không? Mình chạy xe cả ngày, lo lắm', '["\/uploads\/posts\/seed_v2_8.jpg"]', 'question', 1, rand(15,50), rand(10,28)],
['Cuộc sống shipper không ai hiểu: Nắng mưa đều chạy, lễ Tết vẫn giao. Nhưng được cái tự do, không ai quản', '["\/uploads\/posts\/theme_sunset_2.jpg"]', 'post', 0, rand(40,130), rand(10,28)],
['Ae SPX cập nhật: Bưu cục Tân Phú mới mở thêm quầy, lấy hàng nhanh hơn trước nhiều', '["\/uploads\/posts\/seed_v2_9.jpg"]', 'post', 1, rand(10,35), rand(3,10)],
['Review găng tay chống nắng Uniqlo 150k: Mát tay, chống UV tốt, co giãn không vướng tay lái. Đáng mua ae', '["\/uploads\/posts\/seed_v2_10.jpg"]', 'review', 1, rand(22,70), rand(6,18)],
['Trưa nắng 38 độ, ship xong đơn ghé cây xăng rửa mặt cho tỉnh. Ae nhớ uống đủ nước nhé, đừng để say nắng', '["\/uploads\/posts\/seed_v2_11.jpg"]', 'post', 1, rand(25,80), rand(5,15)],
['Chạy ngang qua mấy con đường cây xanh Hà Nội lúc thu, lá rụng vàng đẹp lắm. Shipper được du lịch miễn phí ae', '["\/uploads\/posts\/seed_v2_12.jpg"]', 'post', 0, rand(30,95), rand(5,15)],
['Ae Ninja Van ơi, đơn hoàn mình gửi 3 ngày rồi chưa thấy cập nhật. Gọi tổng đài cũng không ai nghe', '["\/uploads\/posts\/seed_v2_13.jpg"]', 'question', 1, rand(8,28), rand(8,22)],
['Mẹo cho ae mới vào nghề: 2 tuần đầu đừng tham đơn xa, chạy nội quận trước cho quen đường. Dần dần mở rộng ra', '["\/uploads\/posts\/seed_v2_14.jpg"]', 'tip', 0, rand(35,110), rand(10,28)],
]; }

// ===== BATCH 4: Posts 76-100 (Mixed themes) =====
if ($batch == 4) { $posts = [
['Đầu tháng đóng bảo hiểm, tiền thuê nhà, xăng xe... Lương shipper vừa lĩnh đã hết. Ae có cách quản lý tài chính không?', '["\/uploads\/posts\/seed_v2_15.jpg"]', 'question', 1, rand(20,65), rand(12,30)],
['Khoe ảnh "office" của mình: Cả thành phố Sài Gòn. Không sếp, không KPI, chỉ có đường và đơn hàng', '["\/uploads\/posts\/seed_land_1.jpg"]', 'post', 1, rand(45,150), rand(10,28)],
['Ship hàng khu biệt thự Thảo Điền, nhà to quá ae. Bảo vệ kiểm tra kỹ lắm mới cho vào giao', '["\/uploads\/posts\/seed_land_2.jpg"]', 'post', 1, rand(15,50), rand(5,15)],
['Giao đơn cuối cùng trong ngày lúc 21h. Về nhà tắm rửa xong nằm xuống là ngủ luôn. Mệt nhưng vui ae', '["\/uploads\/posts\/seed_land_3.jpg"]', 'post', 0, rand(22,70), rand(5,15)],
['Ae Viettel Post có thấy phí COD tăng không? Hôm qua mình bị trừ nhiều hơn bình thường', '["\/uploads\/posts\/seed_land_4.jpg"]', 'question', 0, rand(8,28), rand(8,22)],
['Mẹo đối phó khách bom hàng: Gọi xác nhận trước khi giao, nếu không nghe 2 lần thì hoàn luôn. Đừng chờ mất thời gian', '["\/uploads\/posts\/seed_v2_16.jpg"]', 'tip', 1, rand(38,120), rand(12,35)],
['Cảm ơn ae cộng đồng ShipperShop. Nhờ mọi người chia sẻ mẹo mà tháng này mình ship hiệu quả hơn, tăng 20% thu nhập', '["\/uploads\/posts\/seed_land_5.jpg"]', 'post', 0, rand(30,95), rand(8,22)],
['Review áo mưa bộ Rando 200k: Chống nước tốt, không bí, có phản quang. Dùng 1 năm vẫn OK. Đáng tiền ae', '["\/uploads\/posts\/theme_rain_0.jpg","\/uploads\/posts\/theme_rain_2.jpg"]', 'review', 1, rand(25,80), rand(8,22)],
['Ship hàng vào ngõ nhỏ phố cổ Hà Nội, xe máy không lọt. Phải gửi xe đi bộ vào giao. Phí gửi xe 5k, lãi ròng còn 10k', '["\/uploads\/posts\/seed_land_7.jpg"]', 'post', 0, rand(20,65), rand(8,22)],
['Hôm nay gặp 1 khách cực dễ thương, bé gái 5 tuổi ra nhận hàng cho mẹ. Tặng bé cái kẹo, bé cười toe toét', '["\/uploads\/posts\/seed_v2_17.jpg"]', 'post', 1, rand(40,130), rand(8,22)],
['Ae ơi ứng dụng nào track quãng đường tốt nhất? Mình đang dùng Strava mà hao pin quá', '["\/uploads\/posts\/seed_v2_18.jpg"]', 'question', 0, rand(10,35), rand(10,25)],
['Sài Gòn 6h sáng, đường vắng, gió mát. Giờ vàng để ship ae ạ - ít kẹt, khách dễ nhận hàng', '["\/uploads\/posts\/seed_land_8.jpg"]', 'post', 1, rand(18,55), rand(4,12)],
['Mẹo tối ưu tuyến đường: Xếp đơn theo cụm, ship gần nhà trước rồi xa dần. Tiết kiệm 30% thời gian di chuyển', '["\/uploads\/posts\/theme_road_1.jpg","\/uploads\/posts\/theme_road_0.jpg"]', 'tip', 0, rand(42,140), rand(12,35)],
['Con Wave 110 trung thành của mình. 50,000km, vẫn chạy ngon. Ae chạy xe gì share coi', '["\/uploads\/posts\/seed_v2_19.jpg"]', 'post', 1, rand(28,90), rand(10,28)],
['Giao hàng ngày nắng nóng 40 độ ở Đà Nẵng. Ae nhớ dùng kem chống nắng và uống 3-4 lít nước/ngày', '["\/uploads\/posts\/seed_land_9.jpg"]', 'tip', 2, rand(20,65), rand(5,15)],
['Đêm giao thừa vẫn ship hàng. Đường vắng tanh, xung quanh người ta đang đón Tết. Buồn nhưng có tiền gấp đôi ae', '["\/uploads\/posts\/seed_v2_20.jpg"]', 'post', 0, rand(50,160), rand(15,40)],
['Kinh nghiệm ship đơn lớn: Dùng dây chằng cao su cố định, bọc nilon chống nước. Đừng tiếc 5k mua dây ae', '["\/uploads\/posts\/theme_package_0.jpg","\/uploads\/posts\/theme_package_2.jpg"]', 'tip', 1, rand(30,95), rand(8,22)],
['Hà Nội mùa hè nóng 38 độ, chạy từ sáng đến trưa đã kiệt sức. Ae nhớ nghỉ ngơi, đừng ham đơn quá mà ngất giữa đường', '["\/uploads\/posts\/seed_land_10.jpg"]', 'post', 0, rand(30,95), rand(8,22)],
['Cảm ơn chị khách Quận 3 tip 50k và tặng chai nước. Những khách hàng dễ thương thế này là động lực của shipper ae', '["\/uploads\/posts\/seed_v2_21.jpg"]', 'post', 1, rand(35,110), rand(8,22)],
['Ae shipper Cần Thơ có đông không? Mình thấy đơn khu miền Tây ít quá, chạy cả ngày được 15 đơn thôi', '["\/uploads\/posts\/seed_land_11.jpg"]', 'question', 6, rand(8,28), rand(6,18)],
['Tổng kết tuần: 180 đơn, thu nhập 4.2 triệu, xăng 600k. Tuần sau phấn đấu 200 đơn ae', '["\/uploads\/posts\/seed_v2_22.jpg"]', 'post', 1, rand(25,80), rand(6,18)],
['Mẹo cuối: Luôn mỉm cười khi giao hàng. Khách vui → đánh giá tốt → được ưu tiên đơn. Nghề nào cũng cần thái độ ae', '["\/uploads\/posts\/seed_v2_23.jpg"]', 'tip', 0, rand(50,160), rand(12,35)],
['Hôm nay đạt mốc 10,000 đơn giao thành công trên GHTK! 2 năm ròng rã, cảm ơn tất cả ae đã đồng hành', '["\/uploads\/posts\/seed_v2_24.jpg"]', 'post', 0, rand(60,200), rand(15,45)],
['Ship đêm khu Phú Mỹ Hưng yên tĩnh, đường rộng thênh thang. Đèn đường sáng trưng, an toàn cho ae chạy đêm', '["\/uploads\/posts\/seed_land_12.jpg"]', 'post', 1, rand(15,50), rand(3,10)],
['Nhắn ae mới vào nghề: Kiên nhẫn, đừng so sánh thu nhập với người khác. Ai cũng bắt đầu từ 10 đơn/ngày. Cố lên!', '["\/uploads\/posts\/seed_v2_25.jpg"]', 'post', 0, rand(55,180), rand(12,35)],
]; }

if (empty($posts)) { echo "Invalid batch (1-4)\n"; exit; }

$inserted = 0;
$startId = $d->fetchOne("SELECT MAX(id) as m FROM posts", [])['m'] + 1;

foreach ($posts as $idx => $p) {
    $content = $p[0];
    $images = $p[1];
    $type = $p[2];
    $provIdx = $p[3];
    $likes = $p[4];
    $comments = $p[5];
    
    $province = $provinces[$provIdx];
    $district = $provIdx == 0 ? $districts_hn[array_rand($districts_hn)] : ($provIdx == 1 ? $districts_hcm[array_rand($districts_hcm)] : '');
    
    // Random user (3-100 range, existing users)
    $userId = rand(3, 100);
    
    // Random time in last 7 days
    $hours = rand(1, 168);
    $created = date('Y-m-d H:i:s', time() - $hours * 3600);
    
    try {
        $d->query("INSERT INTO posts (user_id, content, images, type, likes_count, comments_count, province, district, status, created_at, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)",
            [$userId, $content, $images, $type, $likes, $comments, $province, $district, $created, rand(50, 2000)]);
        $inserted++;
    } catch (Throwable $e) {
        echo "ERR post " . ($idx+1) . ": " . $e->getMessage() . "\n";
    }
}

echo "Batch $batch: Inserted $inserted/" . count($posts) . " posts\n";
echo "Total posts now: " . $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE status='active'", [])['c'] . "\n";
