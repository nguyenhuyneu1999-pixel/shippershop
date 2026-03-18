<?php
set_time_limit(120);
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();$pdo=$d->getConnection();
$extra=[
"Bán xe Wave RSX 2022 còn mới 90%, giá 18tr. Ae ship cần inbox","Giao cho tiệm trà chanh, em pha tặng 1 ly. Ngon đỡ mệt!","Đường Đinh Bộ Lĩnh Bình Thạnh sáng nay kẹt xe 2km vì tai nạn",
"Mẹo: Luôn có ô cắm sạc xe trên xe, vừa chạy vừa sạc pin điện thoại","Ship cho bác sĩ ở BV Bạch Mai. Cảm ơn nhân viên y tế!","Confession: 5h sáng thức dậy ship, 11h đêm mới về. 18h/ngày",
"Review găng tay Mechanix cho shipper: Grip tốt, thoáng khí, 120k","Khu Q.Bình Tân đường rộng mới mở, ship nhanh hơn trước","Ae ơi Grab đổi chính sách thưởng, ai biết chi tiết?",
"Ship cho cửa hàng điện thoại, giao iPhone 15 Pro Max. Hồi hộp!","Mẹo: Dùng Google Lens chụp biển số nhà khi tìm không ra","Trời mưa to Q.9, đường ngập 30cm. Cẩn thận ae nha",
"Confession: Shipper mà hay ăn vặt. Mỗi ngày ghé 3-4 quán","Thu nhập shipper part-time sinh viên: 5-7tr/tháng. Đủ xài","Khu Nhà Bè đường về quê vắng, ship đêm hơi sợ",
"Giao cho quán lẩu, nồi lẩu nóng hổi. Cầm mà nóng tay","Review khóa vân tay xe máy Viro: 800k, chống trộm hiệu quả","Ae Huế: Mùa mưa bão, cẩn thận gió mạnh khi qua cầu",
"Mẹo: Set Google Maps chế độ xe máy, chỉ đường chính xác hơn","Ship cho trường mẫu giáo, trẻ con chạy ra xem đồ. Dễ thương!","Khu Tân Cảng cảng biển, container nhiều. Cẩn thận ae",
"Confession: Có hôm buồn ngủ quá suýt tông cột đèn","Giao 60 đơn hôm nay. Mệt nhưng happy khi check app thấy tiền","Ae Đà Lạt: Đường đèo ban đêm sương mù, bật đèn sáng nhé",
"Mẹo: Luôn mang theo giấy ăn và nước, cơ bản nhưng quan trọng","Ship cho shop thú cưng, chó mèo nhìn theo xe. Dễ thương quá 🐱","Thu nhập Q1/2026: 55 triệu. Mục tiêu Q2 là 60 triệu",
"Review nón Protec cho shipper: Nhẹ, thoáng, kính chống UV, 250k","Khu Thanh Xuân HN: Nhiều hẻm nhỏ, GPS hay chỉ sai","Confession: Ship cho nhà mình mà giả bộ không quen 😂",
"Giao hàng lúc 6h sáng, trời mát mẻ. Thích nhất là ship sáng sớm","Ae Vũng Tàu: Cuối tuần đơn tăng 50%, đặc biệt khu Back Beach","Mẹo: Check thời tiết trước khi nhận đơn, tránh mưa bất ngờ",
"Ship cho quán cơm tấm sáng, mùi sườn nướng thơm phức","Khu Q.Tân Phú: Nhiều công ty, đơn office hours rất nhiều","Review thùng ship Lalamove: Lớn, chắc chắn, giá 150k",
"Confession: Ship 3 năm, lưng còng, đầu gối đau. Nghề vất vả thật","Giao cho khách ruột lần thứ 100. Khách tặng thiệp cảm ơn ❤️","Ae Bình Dương: KCN Sóng Thần đơn nhiều, đặc biệt cuối tháng",
"Mẹo: Tập thể dục 15 phút mỗi sáng, lưng đỡ đau hẳn","Ship cho quán karaoke, giao 30 phần đồ ăn cùng lúc","Khu Hải Châu ĐN: Đường sạch, ít kẹt, ship sướng nhất miền Trung",
"Confession: Có lần ship mà đi nhầm thành phố. GPS chỉ sai tỉnh","Thu nhập shipper Đà Nẵng: 12-15tr/tháng, chi phí thấp hơn SG","Review áo mưa Rando cho shipper: Chống nước tốt, 80k/bộ",
"Giao cho tiệm vàng lúc 10h đêm. Hồi hộp và sợ cướp","Ae Cần Thơ: Khu Ninh Kiều đơn du lịch nhiều, đặc biệt cuối tuần","Mẹo: Luôn verify mã đơn trước khi giao. Tránh giao nhầm",
"Ship cho em bé đặt quà bí mật tặng bố. Xúc động quá 🎁","Khu Long Biên HN: Cầu Long Biên view đẹp, ship vừa đi vừa ngắm","Review lốp Michelin City Extra: Bám đường tốt khi mưa, 400k/cặp",
"Confession: Lương tháng cao nhất 32 triệu. Tháng thấp nhất 8 triệu","Giao cho khách ngoại quốc, tip bằng chocolate ngoại nhập","Ae TP.HCM: Khu Thảo Điền Q.2 nhiều expat, đơn food cao cấp",
"Mẹo: Dùng app CamScanner chụp lại biên lai giao hàng","Ship cho quán phở Thìn nổi tiếng HN, đơn ship xa tận Q.Cầu Giấy","Khu Hà Đông HN: Mới phát triển, đường rộng, đơn tăng nhanh",
"Confession: Đã từng ước ship xong sẽ nghỉ. Nhưng rồi lại ship tiếp","Giao cho bà cụ 90 tuổi ở một mình. Giúp bà xách đồ lên lầu","Ae ship beer: Bia lạnh giao nhanh thì tip cao, giao chậm thì bị rate xấu",
"Review sạc ô tô 2 cổng cho shipper: 100k, sạc nhanh QC3.0","Khu Q.12 đường Tô Ký: Đông dân, đơn nhiều nhưng hẻm nhỏ","Mẹo: Giữ xe sạch sẽ, khách đánh giá shipper qua xe đầu tiên",
"Confession: Ngày cuối năm giao 70 đơn, về nhà đã 12h đêm. Tết vui!","Giao đồ cho đêm nhạc ở phố đi bộ Nguyễn Huệ. Sôi động!","Ae Hà Nội: Đường Láng kẹt từ 7h-9h, nên đi Kim Mã thay thế",
"Ship cho nhà sách, giao 50 cuốn sách nặng 20kg. Đau lưng quá","Khu Q.3 SG: Phố cafe, giao coffee đi coffee luôn ☕","Review balance đứng cho shipper: 200k, giảm đau lưng hiệu quả",
"Mẹo cuối: Shipper không phải nghề tạm. Làm chuyên nghiệp, kiếm tiền tốt!","Giao hàng Tết Trung Thu: Bánh đầy xe, thơm cả đường đi 🥮","Ship đêm Noel: Đường phố lung linh, giao quà Giáng sinh thật vui 🎄",
"Ae mới ship: Tuần đầu sẽ mệt, nhưng tuần thứ 2 đã quen. Cố lên!","Khu sân bay Nội Bài: Giao hàng cho phi hành đoàn, hành khách","Review bình nước giữ lạnh cho shipper: Uống nước mát cả ngày, 80k",
"Confession cuối: Yêu nghề shipper vì tự do, vì con đường, vì mọi người ❤️","Giao xong 100 đơn cuối tuần. Thưởng cho mình tô bún bò Huế","Ship ở Phan Thiết: Biển đẹp, hải sản tươi. Giao xong tắm biển luôn",
"Ae cả nước: Hãy tự hào vì nghề shipper. Chúng ta kết nối triệu gia đình 🇻🇳","Mẹo: Ngày nào cũng cười với khách. Nụ cười tốn 0 đồng nhưng giá trị vô hạn",
];
$userIds=$d->fetchAll("SELECT id FROM users WHERE id > 1 ORDER BY RAND() LIMIT 200");
$uids=array_column($userIds,'id');
$provinces=['Hồ Chí Minh','Hà Nội','Đà Nẵng','Cần Thơ','Bình Dương','Đồng Nai'];
$dists=['Quận 1','Quận 7','Bình Thạnh','Thủ Đức','Gò Vấp','Tân Bình','Cầu Giấy','Đống Đa','Ba Đình'];
$types=['post','review','question','tip'];
$imgDir='/home/nhshiw2j/public_html/uploads/posts/';
$imgs=[];foreach(glob($imgDir.'seed_*.jpg') as $f){$imgs[]='/uploads/posts/'.basename($f);}
$cmts=['Đúng rồi!','Hay quá 👍','Like mạnh!','Cảm ơn bạn','Shipper cố lên 💪','Kinh nghiệm quý','Bookmark','Chia sẻ thêm đi','Ae đoàn kết!','Thanks!','Mình cũng vậy','Confirm đúng!','Good job!','Respect!','Keep going!'];
$pdo->beginTransaction();
try{
$ins=0;$lc=0;$cc=0;
foreach($extra as $content){
    $uid=$uids[array_rand($uids)];
    $prov=$provinces[array_rand($provinces)];
    $dist=$dists[array_rand($dists)];
    $type=$types[array_rand($types)];
    $imgJson=(rand(1,100)<=70&&count($imgs)>0)?json_encode([$imgs[array_rand($imgs)]]):null;
    $likes=rand(5,150);$comments=rand(1,30);$shares=rand(0,20);
    $ca=date('Y-m-d H:i:s',time()-rand(1,720)*3600);
    $stmt=$pdo->prepare("INSERT INTO posts (user_id,content,type,images,likes_count,comments_count,shares_count,province,district,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$uid,$content,$type,$imgJson,$likes,$comments,$shares,$prov,$dist,'active',$ca]);
    $pid=$pdo->lastInsertId();
    $ins++;
    $nl=rand(3,10);$likers=array_rand(array_flip($uids),min($nl,count($uids)));
    if(!is_array($likers))$likers=[$likers];
    foreach($likers as $lu){$pdo->prepare("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$lu]);$lc++;}
    $nc=rand(1,4);for($j=0;$j<$nc;$j++){$cu=$uids[array_rand($uids)];$pdo->prepare("INSERT INTO comments (post_id,user_id,content,`status`,created_at) VALUES (?,?,?,'active',?)")->execute([$pid,$cu,$cmts[array_rand($cmts)],date('Y-m-d H:i:s',time()-rand(60,86400))]);$cc++;}
}
$pdo->commit();
echo "Extra: $ins posts, $lc likes, $cc comments\n";
echo "TOTAL posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts")['c']."\n";
echo "TOTAL comments: ".$d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']."\n";
echo "TOTAL likes: ".$d->fetchOne("SELECT COUNT(*) as c FROM post_likes")['c']."\n";
}catch(Exception $e){$pdo->rollback();echo "ERROR: ".$e->getMessage()."\n";}
