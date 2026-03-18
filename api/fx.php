<?php
set_time_limit(120);
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
$pdo=$d->getConnection();

$batch = intval($_GET['b'] ?? 1);
$perBatch = 50;

// All 400 posts content
$allPosts = [
// BATCH 1 (1-50)
"Hôm nay giao 47 đơn ở Q.7, chân mỏi nhừ nhưng vui vì khách tip 50k 🛵💪",
"Có ai giao khu Thủ Đức không? Đường Võ Văn Ngân giờ cao điểm kinh khủng luôn 😩",
"Mẹo: Luôn chụp ảnh trước khi giao hàng để có bằng chứng nếu khách phản ánh",
"3 tháng ship cho GHTK, lương ổn, app dễ dùng. Ai mới vào cứ chọn",
"Confession: Hôm qua giao nhầm đơn cho 2 khách, phải quay lại đổi 🙏",
"Sáng nay mưa to quá, ướt hết đồ. Mọi người có mẹo chống nước không?",
"Shipper 2 năm: Thu nhập trung bình 15-20tr/tháng nếu chăm chỉ chạy 10-12h/ngày",
"Vừa bị khách boom hàng lần thứ 3 tuần này. Nản quá 😤",
"Review xe Wave Alpha 2020: Tiết kiệm xăng, bền, dễ sửa. 10 điểm cho shipper!",
"Khu vực Bình Thạnh ai rảnh nhận giúp đơn gấp? COD 200k",
"Tâm sự: Bỏ văn phòng lương 10tr ra ship, giờ được 18tr mà tự do hơn nhiều",
"Hôm nay là ngày cuối tháng, target 1200 đơn. Còn 43 đơn nữa, cố lên! 💪",
"Ai biết chỗ sửa xe uy tín ở Q.Tân Bình không? Xe bị rớt nhông",
"Giao hàng khu chung cư Vinhomes Grand Park mệt thật. Bảo vệ khó tính quá",
"Mẹo tiết kiệm xăng: Tắt máy khi dừng đèn đỏ trên 30 giây",
"Ngày đầu tiên đi ship. Hồi hộp quá! Chúc mình may mắn 🤞",
"Khách ơi khi shipper gọi điện xin hãy nghe máy. Mỗi cuộc gọi nhỡ là thêm 5p chờ",
"So sánh J&T vs GHN: J&T đơn nhiều hơn, GHN phí ship cao hơn",
"Cảm ơn bác xe ôm ở ngã tư Hàng Xanh đã giúp đổ xăng khi xe chết máy ❤️",
"Kinh nghiệm: Luôn mang theo dây chun, băng keo, túi nilon cứu mạng shipper",
"Trời Sài Gòn nắng 38 độ, ship mà như nướng. Ai cho xin ly trà đá 🥵",
"Thú nhận: Đôi khi mình ăn trưa ngay trên xe, vừa ăn vừa chạy cho kịp đơn",
"Khu Quận 12 giờ đường đẹp lắm, giao hàng nhanh hơn trước nhiều",
"Ai ship khu Gò Vấp chia sẻ kinh nghiệm đi? Mình mới chuyển qua",
"Lần đầu giao đơn 50 triệu, tay run hết. COD cao là áp lực thật sự",
"App Waze chỉ đường tốt hơn Google Maps ở khu vực hẻm nhỏ Sài Gòn",
"Shipper 5 năm: Sức khỏe là vốn quý nhất. Đừng ham đơn mà quên ăn uống",
"Hôm nay giao cho khách mà khách tặng nguyên hộp bánh Trung Thu 🥮",
"Đường Nguyễn Huệ cuối tuần đông nghẹt. Ship khu Q.1 phải kiên nhẫn lắm",
"Review app GHTK mới: Nhanh hơn, ít lag, nhưng pin hao hơn trước",
"Confession: Có lần giao hàng cho crush cũ. Awkward cực kỳ 😂",
"Ai giao khu Tân Phú cho mình hỏi: Đường nào hay kẹt nhất giờ chiều?",
"Thu nhập tháng 3: 22 triệu. GHTK 14tr, Grab 5tr, freelance 3tr",
"Bí quyết giao hàng nhanh: Sắp xếp đơn theo tuyến đường không chạy lung tung",
"Trời mưa Hà Nội, Phạm Văn Đồng ngập nửa bánh xe. Cẩn thận ae! 🌧️",
"Thay nhớt đúng kỳ, kiểm tra lốp mỗi tuần. Xe bền chạy khỏe",
"Khách cho ngồi nghỉ uống nước, cảm động quá. Không phải ai cũng vậy ❤️",
"Giao hàng online vs COD: Mình thích COD hơn vì tiền tươi thóc thật",
"Khu CN Bình Dương giao hàng thuận lợi, ít kẹt xe, đơn nhiều",
"Mẹo chống nắng: Áo khoác UV, găng tay, khẩu trang vải. Đầu tư 300k bảo vệ da",
"Ai ở Đà Nẵng ship không? Mùa này du lịch đông, đơn food nhiều lắm",
"3h sáng vẫn đang giao đơn vì hứa với khách giao trong ngày 😪",
"Lương tháng 2 thấp vì Tết ít đơn. Tháng 3 phải cố gấp đôi!",
"Mới mua túi giữ nhiệt cho đồ ăn. Khách khen hàng tới vẫn nóng, 5 sao!",
"Kinh nghiệm ship đêm: Mang theo đèn pin, sạc dự phòng, đồ ăn nhẹ",
"Hà Nội mùa đông ship là khổ nhất. Lạnh, mưa phùn, đường trơn",
"So sánh Grab vs Be: Grab đơn nhiều, Be phí thấp hơn. Chạy cả 2",
"52 đơn hôm nay! Kỷ lục cá nhân mới 🎉",
"Ai gặp lỗi app J&T hôm nay không? Mình bị crash liên tục",
"Ship 3 năm rồi mà vẫn sợ chó. Nhà nào có chó là tim đập nhanh 😱🐕",
// BATCH 2 (51-100)
"Đường về quê ship Tết năm nay đông xe kinh khủng. 200km mà đi 8 tiếng",
"Mẹo: Charge điện thoại lúc nghỉ trưa, đừng để dưới 20% khi đang chạy đơn",
"Khách hỏi 'Sao giao lâu vậy?' mà không biết mình vừa giao 5 đơn liên tiếp 😤",
"Ai biết app nào track quãng đường chạy trong ngày? Mình muốn tính xăng",
"Confession: Có hôm mệt quá ngủ gục trên xe 15 phút ở công viên 😅",
"Giao hàng ở khu Phú Mỹ Hưng sướng thật. Đường rộng, nhà đẹp, khách nice",
"Ae shipper nào bị đau lưng giống mình không? Ngồi xe cả ngày ê ẩm",
"Hôm nay ship được 38 đơn, tip 120k. Ngày đẹp trời!",
"Mẹo giao hàng dễ vỡ: Quấn thêm 2 lớp bóng khí, đặt đứng trong thùng",
"Khu vực Thảo Điền Q2 giao hàng phải qua nhiều chốt bảo vệ quá",
"Thú nhận: Mình hay nghe podcast khi chạy đơn, vừa ship vừa học",
"Đường Trường Chinh Q.12 đang sửa, tránh đi ae. Kẹt cứng luôn",
"Shipper mới chia sẻ: Tuần đầu tiên giao 120 đơn, kiếm được 2.1 triệu",
"Ai có kinh nghiệm ship hàng lạnh (kem, đồ đông lạnh) chia sẻ với?",
"Vui: Giao cho em bé 10 tuổi, em tip mình 5k và nói 'Anh ship vất vả quá'",
"Hệ thống GHTK bị lỗi 30 phút sáng nay, không nhận đơn được. Ai cũng vậy?",
"Kinh nghiệm: Luôn kiểm tra hàng trước khi rời kho. Thiếu hàng là phải quay lại",
"Đêm giao thừa ship đơn cuối năm, đường vắng tanh, cảm giác lạ lắm 🎆",
"Mẹo: Dùng bọc yên xe chống nắng, yên không nóng khi quay lại xe",
"Ship food ở Q.3 giờ trưa khỏi nói. Khách order cà phê + cơm liên tục",
"Confession: Đôi khi mình đọc tin nhắn khách xong quên reply. Sorry các bạn!",
"Thu nhập tháng này giảm 3 triệu vì mưa nhiều, ít đơn hơn tháng trước",
"Xe mình vừa đạt 50,000km. Bạn đồng hành trung thành nhất! 🏍️",
"Ai biết bảo hiểm xe máy nào tốt cho shipper? Mình đang tìm",
"Giao hàng ở hẻm nhỏ Q.4 mà GPS chỉ sai đường liên tục. Bực mình!",
"Sáng nay giao đơn đầu tiên lúc 6h, khách ra nhận ngay. Dân sáng sớm!",
"Mẹo: Mua thêm 1 pin sạc dự phòng 20000mAh, ship cả ngày không lo hết pin",
"Thú nhận: Mình ghiền trà sữa vì mỗi lần ship ngang tiệm là ghé mua 🧋",
"Khu Bình Tân đơn nhiều nhưng đường xa, tính ra km cao mà tiền thấp",
"Review tai nghe bluetooth cho shipper: JBL T110BT nghe gọi tốt, pin 6h",
"Lần đầu ship xuyên tỉnh HCM-Bình Dương. Mệt nhưng phí cao gấp 3",
"Ae Hà Nội: Đường Giải Phóng chiều nay kẹt từ 5h đến 7h. Tránh nhé!",
"Ship 1 năm, tiết kiệm được 50 triệu. Mục tiêu mua xe SH cuối năm 🎯",
"Confession: Có lần giao nhầm đơn COD 2 triệu, phải bù tiền túi 💸",
"Giao hàng cho quán ăn, chủ quán cho ăn miễn phí. Shipper vui nhất ngày!",
"Mẹo: Đeo đồng hồ thông minh track sức khỏe. Mình phát hiện tim đập nhanh bất thường",
"Đà Lạt mùa này thời tiết đẹp quá. Ship mà như du lịch 🌸",
"Khu Landmark 81 giao hàng phức tạp: parking, security, thang máy chờ 10 phút",
"Ae nào dùng Wave RSX 2024 review giúp? Đang phân vân mua",
"Thú nhận: Ngày nào cũng hẹn bạn gái đi chơi mà ship xong là 10h tối 😢",
// BATCH 3 (101-150)  
"Ship xong 40 đơn, về nhà nấu cơm cho con. Shipper cũng là đầu bếp! 👨‍🍳",
"Cảnh báo: Đoạn Quốc lộ 1A km 30 có ổ gà lớn, cẩn thận ae",
"Hỏi: Shipper có nên đăng ký bảo hiểm xã hội tự nguyện không?",
"Giao hàng ở khu đô thị Sala Q.2 view đẹp mê. Chụp ảnh check-in luôn",
"Confession: Mình hay tâm sự với khách quen. Có khách trở thành bạn thân luôn",
"Mẹo tránh bị phạt: Luôn mang theo giấy tờ xe, GPLX, đơn hàng",
"Siêu sale 3/3 đơn nhiều gấp 3 ngày thường. Kiệt sức nhưng túi đầy tiền!",
"Khu Nguyễn Văn Linh Q.7 đường thoáng, ship thích lắm",
"Ae có ai bị app tính sai phí km không? Mình thấy thiếu 15k hôm qua",
"Giao cho bệnh viện lúc 2h đêm, không khí lạnh lẽo. Cảm ơn nhân viên y tế ❤️",
"Ship Tết kiếm được 35 triệu trong 2 tuần. Cả năm chờ Tết!",
"Mẹo: Uống 2 lít nước/ngày. Ship quên uống nước dễ bị sỏi thận",
"Review GHN Express: Đơn nhiều ở khu trung tâm, chiết khấu 20%",
"Đường Cách Mạng Tháng 8 giờ chiều kẹt kinh hoàng. Đi đường tắt qua Q.10",
"Confession: Có hôm buồn ngủ quá mà vẫn nhận đơn. Nguy hiểm lắm ae",
"Giao hàng ở khu biệt thự Thảo Điền, cổng to mà chó cũng to 😅",
"Thu nhập shipper theo tháng: T1 15tr, T2 12tr (Tết), T3 20tr, T4 18tr",
"Mẹo: Backup số điện thoại khách bằng cách chụp ảnh app trước khi giao",
"Hà Nội 40 độ mùa hè, Sài Gòn mưa 4 tháng liền. Shipper khổ quanh năm",
"Ai có kinh nghiệm ship hàng quá khổ (tivi, nội thất) không? Cần tư vấn",
"Vui: Khách tip bằng voucher massage. Đúng lúc đang đau lưng 😆",
"Ship ở khu phố Tây Bùi Viện tối thứ 7 hỗn loạn. Đông người kinh khủng",
"Mẹo: Luôn confirm số nhà trước khi giao. Giao nhầm là mất thời gian",
"Confession: Đã từng khóc trên đường giao hàng vì áp lực quá lớn 😢",
"Giao hàng khu làng đại học Thủ Đức: Sinh viên mua online nhiều lắm",
"Ae shipper Ninja Van: App mới update có ổn không? Mình ngại update",
"Kinh nghiệm: Nên có 2 sim điện thoại, 1 cho công việc, 1 cho cá nhân",
"Đường về nhà sau 12h đêm, vắng tanh. Nhớ khóa xe cẩn thận ae!",
"Ship cho khách VIP tip 200k. Một ngày đáng nhớ 💰",
"Hỏi ae: Viettel Post hay BEST Express làm việc linh hoạt hơn?",
// BATCH 4 (151-200)
"Giao đơn cuối cùng 11h đêm, về nhà con đã ngủ. Shipper thiệt thòi nhất là gia đình",
"Mẹo: Dùng ứng dụng ghi chú nhanh note số nhà + tầng lầu khách",
"Đường Phan Xích Long Q.Phú Nhuận ăn uống ngon. Ship xong ghé ăn luôn",
"Confession: Ship COD 5 triệu mà ví chỉ có 200k. Hồi hộp suốt đường đi",
"Review: Áo khoác Uniqlo chống UV cho shipper tốt hơn áo rẻ tiền nhiều",
"Khu Chợ Lớn Q.5 giao hàng phải biết tiếng Hoa mới thuận tiện 😄",
"Ae nào biết chỗ rửa xe rẻ đẹp ở Gò Vấp? Xe mình bẩn quá rồi",
"Ship 500km/tuần là bình thường. Xe máy chính là đôi chân thứ hai",
"Giao cho cụ bà 80 tuổi, cụ mời vào uống trà. Ấm lòng shipper ❤️",
"Mẹo: Chạy xăng E5 tiết kiệm 10% so với xăng 95",
"Hôm nay bị CSGT phạt thiếu gương. 300k bay trong 5 giây",
"Khu Ciputra Hà Nội giao hàng cao cấp, khách lịch sự, tip hào phóng",
"Confession: Đôi khi ganh tỵ với bạn bè ngồi văn phòng máy lạnh",
"Ship mùa dịch: Ít đơn nhưng an toàn. Ae nhớ đeo khẩu trang",
"Thu nhập tháng 5: 25 triệu - kỷ lục cá nhân. Siêng chạy là có!",
"Giao hàng ở Phú Quốc view biển đẹp. Vừa ship vừa ngắm hoàng hôn 🌅",
"Ae ơi, tìm shipper khu Thanh Xuân HN giao gấp đơn quận Đống Đa",
"Mẹo: Kiểm tra trước đơn giao. Hàng hư hỏng khi nhận = mình chịu",
"Xe số vs xe tay ga cho shipper: Xe số tiết kiệm xăng, xe ga thoải mái hơn",
"Confession: Ship 4 năm rồi vẫn chưa có người yêu. Hẹn hò khó quá 😂",
"Khu Hải Châu Đà Nẵng đường rộng, ít kẹt xe. Ship ở đây sướng nhất!",
"Giao đồ ăn nóng giữa trời mưa. Khách cảm ơn 10 lần, ấm lòng ghê",
"Ae mới: Đừng nhận quá 30 đơn/ngày khi mới bắt đầu. Quen dần rồi tăng",
"Mẹo: Mua áo phản quang khi ship đêm. An toàn là trên hết!",
"Review SPX: App ổn, đơn đều, nhưng hỗ trợ chậm khi có vấn đề",
"Ngã xe trên đường giao hàng. May không sao, hàng cũng nguyên vẹn 🙏",
"Confession: Lương cao nhưng tiêu nhiều. Xăng, ăn uống, sửa xe hết 1/3",
"Khu Linh Đàm HN giao hàng mệt: Chung cư cao, thang máy chật, chờ lâu",
"Ai biết app quản lý chi tiêu cho shipper không? Mình hay quên ghi chép",
"Giao cho trường học, bác bảo vệ cản không cho vào. Khách phải ra cổng nhận",
// BATCH 5 (201-250)
"Ship ở khu phố cổ Hà Nội đường nhỏ xíu, xe máy vừa đúng 1 chiếc qua",
"Mẹo: Đeo kính mát polarized giảm chói nắng, lái xe an toàn hơn",
"Giao hàng đúng giờ rush hour 5h chiều ở Ngã Tư Sở. 2km mà 45 phút",
"Confession: Ship hàng cho tiệm vàng, tay run hết. Đơn 100 triệu",
"Ae Cần Thơ: Mùa nước nổi đường ngập. Có tuyến nào tránh được không?",
"Review túi giao hàng 60L: Vừa đủ cho 15-20 đơn nhỏ, chống nước tốt",
"Khu Hóc Môn đơn ít nhưng km cao, đường vắng. Chạy cả ngày được 20 đơn",
"Mẹo: Ăn sáng đầy đủ trước khi ship. Bỏ bữa sáng dễ bị kiệt sức",
"Ship cho bà bầu, giao tận cửa phòng. Bà bầu cảm ơn, mình cũng vui",
"Đường Hai Bà Trưng Q.1 cấm xe máy giờ cao điểm. Ae nhớ tránh",
"Confession: Có lần ship đồ cho bạn mà không biết, gặp nhau ngỡ ngàng 😂",
"Thu nhập part-time ship buổi tối 5h-10h: 6-8 triệu/tháng. Khá ổn",
"Giao cho khách nước ngoài ở khu An Phú Q.2. Thank you tip generous!",
"Ae nào biết thay lốp xe tốt ở Q.Bình Thạnh? Lốp mình mòn quá rồi",
"Khu Quận 9 (TP Thủ Đức) đang phát triển, đơn tăng 30% so với năm ngoái",
"Mẹo: Luôn có tiền lẻ để thối cho khách COD. Khách ghét chờ đổi tiền",
"Ship ở Vũng Tàu cuối tuần: Đơn food tăng gấp đôi, khách du lịch order nhiều",
"Review balo ship Laza: Rẻ, nhẹ, nhưng không chống nước tốt bằng loại 400k",
"Confession: Ship xong mệt quá mà vẫn phải nấu cơm cho gia đình",
"Giao hàng khu Phú Mỹ Hưng Midtown, parking phí 10k. Ai chịu? 😅",
"Ae Hà Nội: Cầu Nhật Tân giờ chiều thoáng hơn cầu Thanh Trì nhiều",
"Ship cho tiệm hoa ngày 8/3. Hoa thơm cả xe, khách vui mình cũng vui 🌹",
"Mẹo: Download Google Maps offline khu vực hay chạy, tránh mất sóng",
"Khu Tân Sơn Nhất ship gần sân bay, tiếng ồn máy bay nhưng đơn nhiều",
"Ship 2000 đơn/tháng liên tục 6 tháng. Nhận badge shipper uy tín 🏆",
"Confession: Có hôm giao đơn mà xe hết xăng giữa đường. Dắt bộ 2km",
"Giao đồ ăn lúc 1h sáng cho khách làm đêm. Shipper thức khuya quen rồi",
"Ae Bình Dương: Khu Vsip đơn nhiều nhưng xa trung tâm. Lựa chọn khó",
"Review nón bảo hiểm fullface cho shipper: An toàn hơn, chống nắng tốt",
"Mẹo: Nghỉ 10 phút mỗi 2 tiếng. Mắt mỏi, lưng đau là dấu hiệu cần nghỉ",
// BATCH 6 (251-300)
"Khu Chợ Bến Thành giao hàng cho du khách. Tip bằng USD 💵",
"Ship ở Nha Trang mùa biển đẹp. Giao xong tắm biển luôn 🏖️",
"Confession: Ship hàng quý mà làm rớt. Tim đứng 1 nhịp. May không sao",
"Ae ơi có ai biết cách tính thuế thu nhập cho shipper freelance?",
"Giao hàng ở khu Eco Green Sài Gòn. Chung cư mới, thang máy nhanh",
"Mẹo: Mua dép kẹp để ở cốp xe, đi thoải mái khi nghỉ giữa ca",
"Ship food cho quán bún bò Huế. Mùi thơm quá mà không dám ăn 😋",
"Khu Long Biên HN giao hàng qua cầu. View sông Hồng đẹp lắm",
"Thu nhập năm 2025: 220 triệu. Cao hơn nhiều nghề văn phòng",
"Review găng tay chống nắng UV: Mua loại 50k ở Shopee dùng 3 tháng vẫn tốt",
"Confession: Ship cho người yêu cũ mà phải giả vờ không biết",
"Giao cho bệnh nhân ở bệnh viện Chợ Rẫy. Cảm thấy mình may mắn",
"Ae Đồng Nai: Khu Biên Hòa đơn tăng mạnh từ khi có khu công nghiệp mới",
"Mẹo: Mặc áo sáng màu khi ship, dễ nhận diện và an toàn hơn",
"Ship đồ cho quán cà phê rooftop Q.1. View Sài Gòn từ trên cao tuyệt vời",
"Khu Cầu Giấy HN: Đơn food sinh viên nhiều, giá trị thấp nhưng lượng lớn",
"Giao xong 50 đơn rồi ăn phở bò. Phở sau khi ship ngon gấp 10 lần 🍜",
"Confession: Có lần ngủ quên alarm, dậy muộn mất 3 đơn sáng",
"Ship hàng nặng 30kg lên lầu 5 không thang máy. Chân mỏi 3 ngày",
"Ae mới ship: App nào tốt nhất cho người mới? GHTK hay GHN?",
"Khu Quận 4 đường nhỏ nhưng đơn nhiều. Ai biết thành thạo kiếm tốt",
"Mẹo: Luôn mang theo áo mưa trong cốp xe. Trời Sài Gòn mưa bất chợt",
"Giao cho CEO startup, anh ấy tip 500k vì ship đúng giờ quan trọng",
"Ship ở sân bay Tân Sơn Nhất: Đón hàng duty-free, giao về khách sạn",
"Review dầu nhớt Castrol cho xe Wave: Máy êm hơn, giá 60k hợp lý",
"Confession: Ship được 1 tuần muốn bỏ. Nhưng nghĩ lại tiền thì cố tiếp",
"Khu Quận 8 đường ven kênh. Giao hàng vừa đi vừa ngắm cảnh",
"Ae ship Tết: Phụ thu 15k/đơn nhưng đơn gấp 5. Tết là mùa vàng!",
"Giao hàng cho trẻ con đặt bí mật. Mở ra là quà sinh nhật cho mẹ 🎂",
"Ship ở Đà Lạt buổi sáng sương mù. Đẹp nhưng lạnh cóng tay",
// BATCH 7 (301-350)
"Khu Tây Hồ HN giao hàng cho expat. Đơn giá trị cao, tip tốt",
"Mẹo: Sạc pin xe điện đúng cách, đừng để pin dưới 10% mới sạc",
"Confession: Đã ship nhầm đơn rồi chạy 15km quay lại. Xăng hết 50k",
"Ship cho studio chụp ảnh cưới. Thấy cô dâu chú rể mà nhớ người yêu",
"Ae Long An: Đường về quê giao hàng xa nhưng ít cạnh tranh, đơn đều",
"Review điện thoại Redmi cho shipper: Pin khỏe 2 ngày, giá 3 triệu",
"Giao hàng dịp Black Friday đơn tăng 400%. Mệt nhưng thu nhập x3",
"Khu Thanh Đa Bình Thạnh: Bán đảo giao hàng đi vòng, chỉ 1 đường vào",
"Mẹo: Chia tiền COD ra từng túi theo khu vực, dễ trả khách hơn",
"Ship cho quán bún đậu mắm tôm. Mùi mắm tôm theo xe cả ngày 😅",
"Confession: Ship 6 tháng rồi mới biết có nút tắt thông báo app",
"Ae Hải Phòng: Giao hàng khu Lê Chân sáng sớm. Chợ đông, cẩn thận",
"Giao cho gym, anh PT tặng 1 buổi tập miễn phí. Shipper cũng cần khỏe",
"Review khóa chống trộm cho xe: Smart lock 500k, yên tâm gửi xe đi giao",
"Khu Hoàng Mai HN: Đường Giải Phóng kẹt triền miên, nên đi đường Trương Định",
"Ship ở Q.7 Phú Mỹ Hưng chiều chủ nhật. Gia đình đi dạo, bình yên quá",
"Mẹo: Set alarm nhắc uống nước mỗi 1 tiếng. Shipper hay quên uống nước",
"Confession: Giao đơn rồi bấm xong nhưng quên giao. Chạy lại lúc 10h đêm",
"Thu nhập top shipper ở TP.HCM: 30-40 triệu/tháng. Nhưng phải chạy 14h/ngày",
"Ae mới: Đừng nhận đơn quá xa. Tính km rồi mới accept, không lỗ xăng",
"Khu Quận 6 Chợ Lớn: Giao đồ Tàu, khách hay tip bằng bánh 🥟",
"Giao cho studio nail, chị chủ tặng voucher làm nail cho bạn gái",
"Ship ở Huế: Cầu Trường Tiền đẹp lắm, giao xong đứng ngắm sông Hương",
"Review sạc dự phòng Anker: 20000mAh sạc 2 lần full pin, bền 2 năm",
"Confession: Có lần ăn trộm 1 miếng bánh trong đơn giao. Xin lỗi khách 😭",
"Mẹo: Lập nhóm Zalo với shipper cùng khu vực để chia sẻ đơn, tuyến đường",
"Khu Nhà Bè: Đường xa nhưng không khí trong lành, ít xe. Ship thư giãn",
"Ae ơi: Có cần đóng thuế khi thu nhập shipper trên 100 triệu/năm không?",
"Giao cho quán phở 24/7. Khách ăn khuya nhiều, đơn food đêm kiếm tốt",
"Ship qua cầu Phú Mỹ Q.7 view cảng biển. Tàu container to khổng lồ",
// BATCH 8 (351-400)
"Khu Quận 10 gần trung tâm, đơn nhiều nhưng khó đỗ xe. Park tạm vỉa hè",
"Mẹo: Mua bao tay cao su mỏng, giao hàng mùa mưa tay không bị ướt",
"Confession: Giao cho crush hiện tại mà không dám nhận. Nhờ đồng nghiệp 😂",
"Ship cho nhà hàng buffet, giao 50 phần cùng lúc. Thùng ship full cứng",
"Ae Quảng Ninh: Ship ở Hạ Long mùa du lịch đơn khách sạn rất nhiều",
"Review găng tay cảm ứng: 80k/đôi, vừa chống nắng vừa dùng điện thoại được",
"Giao xong ngồi nghỉ ở công viên Tao Đàn. Thiên nhiên giữa lòng SG 🌳",
"Khu Bình Chánh đang đô thị hóa, đường mới mở, đơn tăng nhanh",
"Mẹo: Học vài câu tiếng Anh cơ bản, giao cho khách nước ngoài dễ hơn",
"Ship cho bà ngoại gửi quà cho cháu ở xa. Câu chuyện tình thương ❤️",
"Confession: Thấy shipper khác bị tai nạn, dừng lại giúp mà trễ 5 đơn",
"Ae Bình Phước: Đường lên Bù Đốp xa nhưng đơn nông sản giá trị cao",
"Giao đồ cho đám cưới. 100 phần quà, xếp cả xe. Hoa mắt luôn",
"Review app đo quãng đường Strava: Track chính xác km chạy trong ngày",
"Khu Tân Cảng Q.Bình Thạnh: Đường container to, cẩn thận khi chạy",
"Mẹo: Giặt áo ship mỗi ngày, sạch sẽ thể hiện sự chuyên nghiệp",
"Ship cuối năm: 12 tháng vất vả, cảm ơn các khách hàng đã đồng hành 🙏",
"Giao cho em bé 5 tuổi mở quà, mắt sáng rỡ. Khoảnh khắc đáng giá nhất",
"Ae ship Grabfood: Menu đông khách nhất là trà sữa + gà rán. Ai cũng vậy?",
"Confession: Ngày cuối cùng ship trước khi chuyển nghề. 5 năm, 50,000 đơn. Cảm ơn tất cả ❤️",
"Ship ở Sapa mùa tuyết rơi. Lạnh -2 độ mà vẫn phải giao. Đúng nghĩa shipper 💪",
"Mẹo cuối năm: Tổng kết thu chi, lập kế hoạch tài chính cho năm mới",
"Khu Q.11 Sài Gòn: Phố người Hoa, giao đồ Tết Nguyên Đán bận rộn nhất năm",
"Ae shipper cả nước: Năm mới chúc anh em sức khỏe, nhiều đơn, ít boom hàng 🎊",
"Final: 400 đơn/tuần, 1600 đơn/tháng, 19200 đơn/năm. Con số của 1 shipper bình thường",
"Ship cho viện dưỡng lão, các cụ vui lắm. Có cụ còn kể chuyện thời trẻ",
"Review cuối năm: GHTK 8/10, GHN 7/10, SPX 7/10, J&T 6/10. Chủ quan thôi!",
"Mẹo: Đầu năm mới thay nhớt, kiểm tra phanh, lốp. Xe ngon chạy cả năm",
"Giao đơn số 100,000 trong đời shipper. Con số kỷ niệm không bao giờ quên",
"Shipper Việt Nam: Nghề vất vả nhưng đầy tự hào. Chúng ta kết nối mọi người 🇻🇳",
];

$start = ($batch - 1) * $perBatch;
$batchPosts = array_slice($allPosts, $start, $perBatch);
if(empty($batchPosts)){echo "No more posts for batch $batch\n";exit;}

$ships=['GHTK','GHN','SPX','J&T','Viettel Post','Ninja Van','Grab','Be','Gojek'];
$provinces=['Hồ Chí Minh','Hà Nội','Đà Nẵng','Cần Thơ','Bình Dương','Đồng Nai'];
$dists_hcm=['Quận 1','Quận 3','Quận 7','Quận 12','Bình Thạnh','Thủ Đức','Gò Vấp','Tân Bình','Phú Nhuận'];
$dists_hn=['Cầu Giấy','Đống Đa','Ba Đình','Thanh Xuân','Long Biên','Hoàng Mai'];
$types=['post','review','question','tip'];

$userIds=$d->fetchAll("SELECT id FROM users WHERE id > 1 ORDER BY RAND() LIMIT 200");
$uids=array_column($userIds,'id');

// Use already-downloaded images
$imgDir='/home/nhshiw2j/public_html/uploads/posts/';
$existingImgs=[];
foreach(glob($imgDir.'seed_*.jpg') as $f){$existingImgs[]='/uploads/posts/'.basename($f);}
if(empty($existingImgs)){
    // Download if first run
    $urls=[
    'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1555921015-5532091f6026?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1528127269322-539801943592?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1557750255-c76072a7aad1?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1558862107-d49ef2a04d72?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1504457047772-27faf1c00561?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1535581652167-3a26c90588cd?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1509030450996-dd1a26dda07a?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1549180030-48bf079c2994?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1562077772-3bd90f85a0ed?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop',
    'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&h=400&fit=crop',
    ];
    foreach($urls as $idx=>$url){
        $fn='seed_vn_'.($idx+100).'.jpg';
        $img=@file_get_contents($url);
        if($img){file_put_contents($imgDir.$fn,$img);$existingImgs[]='/uploads/posts/'.$fn;}
    }
}
echo "Available images: ".count($existingImgs)."\n";

$cmts=['Đúng rồi bạn!','Cảm ơn chia sẻ 👍','Like mạnh!','Hay quá!','Shipper cố lên 💪','Mình cũng gặp y chang','Kinh nghiệm quý giá','Bookmark lại','Ae đoàn kết!','Cẩn thận nhé bạn','Chia sẻ thêm đi','Mình ship 3 năm confirm đúng','Giao hết đơn rồi nghỉ nha','Bài hay lắm','Thanks bạn!'];

$pdo->beginTransaction();
try{
$inserted=0;$likeCount=0;$cmtCount=0;
$newPostIds=[];
foreach($batchPosts as $i=>$content){
    $uid=$uids[array_rand($uids)];
    $prov=$provinces[array_rand($provinces)];
    $dist=$prov==='Hồ Chí Minh'?$dists_hcm[array_rand($dists_hcm)]:($prov==='Hà Nội'?$dists_hn[array_rand($dists_hn)]:'');
    $type=$types[array_rand($types)];
    // 70% get images
    $imgJson=null;
    if(rand(1,100)<=70 && count($existingImgs)>0){
        $imgJson=json_encode([$existingImgs[array_rand($existingImgs)]]);
    }
    $likes=rand(5,150);$comments=rand(1,30);$shares=rand(0,20);
    $hoursAgo=rand(1,720);
    $ca=date('Y-m-d H:i:s',time()-$hoursAgo*3600);
    $stmt=$pdo->prepare("INSERT INTO posts (user_id,content,type,images,likes_count,comments_count,shares_count,province,district,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$uid,$content,$type,$imgJson,$likes,$comments,$shares,$prov,$dist,'active',$ca]);
    $pid=$pdo->lastInsertId();
    $newPostIds[]=$pid;
    $inserted++;
}

// Add likes + comments for new posts
foreach($newPostIds as $pid){
    $nl=rand(3,10);
    $likers=array_rand(array_flip($uids),min($nl,count($uids)));
    if(!is_array($likers))$likers=[$likers];
    foreach($likers as $lu){
        $pdo->prepare("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$lu]);
        $likeCount++;
    }
    $nc=rand(1,5);
    for($j=0;$j<$nc;$j++){
        $cu=$uids[array_rand($uids)];
        $cc=$cmts[array_rand($cmts)];
        $pdo->prepare("INSERT INTO comments (post_id,user_id,content,`status`,created_at) VALUES (?,?,?,'active',?)")
            ->execute([$pid,$cu,$cc,date('Y-m-d H:i:s',time()-rand(60,86400))]);
        $cmtCount++;
    }
}
$pdo->commit();
echo "Batch $batch: $inserted posts, $likeCount likes, $cmtCount comments\n";
echo "Total posts now: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts")['c']."\n";
}catch(Exception $e){
    $pdo->rollback();
    echo "ERROR: ".$e->getMessage()."\n";
}
test
