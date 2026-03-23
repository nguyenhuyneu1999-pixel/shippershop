<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$posts = [
    ['type'=>'tip','content'=>'Kinh nghiệm ship hàng nặng: Dùng dây buộc chắc chắn, phân bổ trọng lượng đều 2 bên cốp. Không ship quá 30kg bằng xe máy! #antoan #kinhnghiem'],
    ['type'=>'post','content'=>'Lương tháng 3 đã về! Tháng này chạy 1200 đơn, thu nhập 18 triệu sau trừ xăng. Anh em tháng này thế nào? 💰 #thunhap'],
    ['type'=>'review','content'=>'Review giày chạy ship Bitis Hunter: Đi êm, bền, chống trơn tốt. Mua giảm giá 350k trên Shopee. Dùng 6 tháng vẫn OK! ⭐⭐⭐⭐ #phukien'],
    ['type'=>'question','content'=>'Anh em ơi, xe Wave Alpha hay Sirius để ship? Mình budget 15 triệu xe cũ. Ưu tiên tiết kiệm xăng. #hoidan #xemay'],
    ['type'=>'discussion','content'=>'Shopee đang thay đổi chính sách ship, phí giảm cho seller nhưng shipper không được gì. Mọi người nghĩ sao? #thaoluan #shopee'],
    ['type'=>'tip','content'=>'Mẹo tìm địa chỉ nhanh: Dùng Google Maps + What3Words combo. Khách cho 3 từ là tìm ra chính xác vị trí! #meohay #diacchi'],
    ['type'=>'confession','content'=>'[Confession] Hôm qua giao hàng cho cô gái, cô ấy cho thêm 20k tiền tip + ly trà sữa. Hạnh phúc giản đơn 🥤❤️'],
    ['type'=>'post','content'=>'⚠️ Cảnh báo: Đoạn Xa lộ Hà Nội (km5-7) đang sửa cầu vượt, kẹt xe nặng 7h-9h sáng. Đi đường Nguyễn Xiển thay thế! #kexe'],
    ['type'=>'review','content'=>'So sánh ứng dụng maps: Google Maps vs Vietmap. Google chính xác hơn, Vietmap có cảnh báo tốc độ + camera phạt. Nên dùng cả 2! 🗺️'],
    ['type'=>'tip','content'=>'Bảo vệ lưng khi ship: Ngồi thẳng, nghỉ 5 phút mỗi giờ, tập yoga đơn giản buổi tối. Lưng khỏe = ship lâu dài! 🧘‍♂️ #suckhoe'],
    ['type'=>'post','content'=>'Group offline Đà Nẵng tháng 4: Dự kiến gặp mặt tại quán cà phê Mộc, ngày 5/4. Ai Đà Nẵng comment đăng ký nhé! ☕ #danang #offline'],
    ['type'=>'question','content'=>'Bảo hiểm xe máy bắt buộc + tự nguyện, anh em mua hãng nào? Bảo Việt hay PVI? Có ai claim bảo hiểm thành công không? #baohiem'],
    ['type'=>'tip','content'=>'Tối ưu pin điện thoại khi ship: Tắt Bluetooth, giảm sáng, dùng dark mode, mang sạc dự phòng 20000mAh. Đủ chạy cả ngày! 🔋'],
    ['type'=>'post','content'=>'Kỷ lục mới! Giao 58 đơn trong 1 ngày nhờ tối ưu tuyến đường. Bí quyết: Nhóm đơn theo khu vực, ship vòng tròn không quay lại. 🏆'],
    ['type'=>'discussion','content'=>'Shipper part-time hay full-time? Mình thấy part-time (4-5h/ngày) thu nhập 8-10tr, đủ sống và có thời gian cho gia đình. #thaoluan'],
    ['type'=>'tip','content'=>'Cách giao tiếp với khách khó: Luôn bình tĩnh, xưng em/anh/chị lịch sự, giải thích rõ ràng. 99% khách sẽ hiểu. Đừng cãi nhau! 🙏'],
    ['type'=>'confession','content'=>'[Confession] Ship được 4 năm, giờ mở được tiệm tạp hóa nhỏ + vẫn chạy ship buổi sáng. Cảm ơn nghề đã cho mình vốn bắt đầu 🏪'],
    ['type'=>'review','content'=>'Áo mưa 2 lớp vs poncho: 2 lớp bền hơn, không bị bay gió, giá 200-300k. Poncho rẻ nhưng hay rách. Đầu tư 1 bộ tốt đi! 🌧️ #review'],
    ['type'=>'post','content'=>'Tip: Ghi chú số nhà + landmark rõ ràng cho shipper giúp giao hàng nhanh hơn. Khách nào đọc được thì nhớ nhé! 📝 #khachangiadiachi'],
    ['type'=>'question','content'=>'App nào track chi phí xăng + thu nhập tốt nhất? Mình đang dùng Excel thủ công, muốn chuyển sang app tự động. #hoidan #app'],
];
$userIds=range(3,200);$provs=['Hồ Chí Minh','Hà Nội','Đà Nẵng','Bình Dương','Đồng Nai','Cần Thơ','Hải Phòng'];$dists=['Quận 1','Quận 7','Thủ Đức','Bình Thạnh','Tân Bình','Gò Vấp','Cầu Giấy','Ba Đình','Hải Châu','Bình Tân','Quận 12','Tân Phú'];
$c=0;
foreach($posts as $p){
  $uid=$userIds[array_rand($userIds)];$prov=$provs[array_rand($provs)];$dist=$dists[array_rand($dists)];
  try{$d->query("INSERT INTO posts (user_id,content,type,province,district,`status`,likes_count,comments_count,created_at) VALUES (?,?,?,?,?,'active',?,?,DATE_SUB(NOW(),INTERVAL ? HOUR))",[$uid,$p['content'],$p['type'],$prov,$dist,rand(0,45),rand(0,20),rand(1,120)]);$c++;}catch(Throwable $e){}
}
echo json_encode(['success'=>true,'seeded'=>$c]);
