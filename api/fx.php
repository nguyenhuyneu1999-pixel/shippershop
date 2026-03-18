<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
$pdo=$d->getConnection();

// Vietnamese shipper posts - batch 1 (50 posts)
$contents = [
["Hôm nay giao 47 đơn ở Q.7, chân mỏi nhừ nhưng vui vì khách tip 50k 🛵💪","tip","GHTK"],
["Có ai giao khu Thủ Đức không? Đường Võ Văn Ngân giờ cao điểm kinh khủng luôn 😩","question","GHN"],
["Mẹo: Luôn chụp ảnh trước khi giao hàng để có bằng chứng nếu khách phản ánh. Đã cứu mình nhiều lần rồi","tip","SPX"],
["3 tháng ship cho GHTK, lương ổn, app dễ dùng. Ai mới vào cứ chọn GHTK","review","GHTK"],
["Confession: Hôm qua giao nhầm đơn cho 2 khách, phải quay lại đổi. May khách thông cảm 🙏","post","J&T"],
["Sáng nay mưa to quá, ướt hết đồ. Mọi người có mẹo chống nước cho hàng không?","question","Viettel Post"],
["Shipper 2 năm chia sẻ: Thu nhập trung bình 15-20tr/tháng nếu chăm chỉ, chạy 10-12h/ngày","discussion","GHTK"],
["Vừa bị khách boom hàng lần thứ 3 tuần này. Nản quá 😤","post","GHN"],
["Review xe Wave Alpha 2020 cho ae ship: Tiết kiệm xăng, bền, dễ sửa. 10 điểm!","review","SPX"],
["Khu vực Bình Thạnh ai rảnh nhận giúp đơn gấp? COD 200k","question","Ninja Van"],
["Tâm sự: Bỏ văn phòng lương 10tr ra ship, giờ được 18tr mà tự do hơn nhiều","post","Grab"],
["Hôm nay là ngày cuối tháng, target 1200 đơn. Còn 43 đơn nữa, cố lên! 💪","post","GHTK"],
["Ai biết chỗ sửa xe uy tín ở Q.Tân Bình không? Xe bị rớt nhông","question","Be"],
["Giao hàng khu chung cư Vinhomes Grand Park mệt thật. Bảo vệ khó tính, thang máy chờ lâu","post","GHN"],
["Mẹo tiết kiệm xăng: Tắt máy khi dừng đèn đỏ trên 30 giây, tiết kiệm 15% xăng/tháng","tip","SPX"],
["Ngày đầu tiên đi ship. Hồi hộp quá! Chúc mình may mắn 🤞","post","J&T"],
["Khách ơi, khi shipper gọi điện xin hãy nghe máy. Mỗi cuộc gọi nhỡ là thêm 5-10 phút chờ đợi","post","GHTK"],
["So sánh J&T vs GHN: J&T đơn nhiều hơn, GHN phí ship cao hơn. Ai thích cái nào?","discussion","J&T"],
["Cảm ơn bác xe ôm ở ngã tư Hàng Xanh đã giúp đổ xăng khi xe mình chết máy giữa đường ❤️","post","Grab"],
["Kinh nghiệm: Luôn mang theo dây chun, băng keo, túi nilon. 3 thứ cứu mạng shipper","tip","GHTK"],
["Hôm nay trời Sài Gòn nắng 38 độ, ship mà như nướng. Ai cho xin ly trà đá 🥵","post","GHN"],
["Thú nhận: Đôi khi mình ăn trưa ngay trên xe, vừa ăn vừa chạy cho kịp đơn 😅","post","SPX"],
["Khu Quận 12 giờ đường đẹp lắm, giao hàng nhanh hơn trước nhiều","post","Viettel Post"],
["Ai ship khu Gò Vấp chia sẻ kinh nghiệm đi? Mình mới chuyển qua khu này","question","GHTK"],
["Lần đầu giao đơn 50 triệu, tay run hết. COD cao là áp lực thật sự","post","GHN"],
["Mẹo: App Waze chỉ đường tốt hơn Google Maps ở khu vực hẻm nhỏ Sài Gòn","tip","Grab"],
["Shipper 5 năm chia sẻ: Sức khỏe là vốn quý nhất. Đừng ham đơn mà quên ăn uống","tip","GHTK"],
["Vui quá! Hôm nay giao cho khách mà khách tặng nguyên hộp bánh Trung Thu 🥮","post","SPX"],
["Đường Nguyễn Huệ cuối tuần đông nghẹt. Ship khu trung tâm Q.1 phải kiên nhẫn lắm","post","Be"],
["Review app GHTK phiên bản mới: Nhanh hơn, ít lag, nhưng pin hao hơn","review","GHTK"],
["Confession: Có lần giao hàng cho crush cũ. Awkward cực kỳ 😂","post","GHN"],
["Ai giao khu Tân Phú cho mình hỏi: Đường nào hay kẹt nhất giờ chiều?","question","J&T"],
["Thu nhập tháng 3 của mình: 22 triệu. Chi tiết: GHTK 14tr, Grab 5tr, freelance 3tr","discussion","GHTK"],
["Bí quyết giao hàng nhanh: Sắp xếp đơn theo tuyến đường, không chạy lung tung","tip","SPX"],
["Trời mưa Hà Nội, đường phố Phạm Văn Đồng ngập nửa bánh xe. Cẩn thận ae!","post","GHN"],
["Shipper và xe máy: Thay nhớt đúng kỳ, kiểm tra lốp mỗi tuần. Xe bền chạy khỏe","tip","GHTK"],
["Hôm nay khách cho mình ngồi nghỉ uống nước, cảm động quá. Không phải ai cũng vậy","post","Grab"],
["Giao hàng online vs COD: Cá nhân mình thích COD hơn vì tiền tươi thóc thật","discussion","Viettel Post"],
["Khu công nghiệp Bình Dương giao hàng thuận lợi, ít kẹt xe, đơn nhiều","post","J&T"],
["Mẹo chống nắng cho shipper: Áo khoác UV, găng tay, khẩu trang vải. Đầu tư 300k bảo vệ da","tip","GHTK"],
["Ai ở Đà Nẵng ship không? Mùa này du lịch đông, đơn food nhiều lắm","question","Grab"],
["Confession: 3h sáng vẫn đang giao đơn vì hứa với khách giao trong ngày 😪","post","GHN"],
["Lương tháng 2 thấp hơn tháng 1 vì Tết ít đơn. Tháng 3 phải cố gấp đôi!","post","SPX"],
["Mới mua túi giữ nhiệt cho đồ ăn. Khách khen hàng tới vẫn nóng, 5 sao review!","post","Be"],
["Kinh nghiệm ship đêm: Mang theo đèn pin, sạc dự phòng, và đồ ăn nhẹ","tip","GHTK"],
["Hà Nội mùa đông ship là khổ nhất. Lạnh, mưa phùn, đường trơn. Nhưng đơn nhiều!","post","Viettel Post"],
["So sánh Grab vs Be: Grab đơn nhiều, Be phí thấp hơn. Mình chạy cả 2","review","Grab"],
["Vừa giao xong đơn cuối cùng trong ngày. 52 đơn! Kỷ lục cá nhân mới 🎉","post","GHTK"],
["Có ai gặp lỗi app J&T hôm nay không? Mình bị crash liên tục","question","J&T"],
["Thú nhận: Ship 3 năm rồi mà vẫn sợ chó. Nhà nào có chó là tim đập nhanh 😱🐕","post","GHN"],
];

// Ship companies + provinces
$ships=['GHTK','GHN','SPX','J&T','Viettel Post','Ninja Van','Grab','Be','Gojek'];
$provinces=['Hồ Chí Minh','Hà Nội','Đà Nẵng','Cần Thơ','Bình Dương','Đồng Nai','Hải Phòng','Long An'];
$districts_hcm=['Quận 1','Quận 3','Quận 7','Quận 12','Bình Thạnh','Thủ Đức','Gò Vấp','Tân Bình','Tân Phú','Phú Nhuận'];
$districts_hn=['Cầu Giấy','Đống Đa','Ba Đình','Thanh Xuân','Long Biên','Hoàng Mai'];

// Get random user IDs
$userIds=$d->fetchAll("SELECT id FROM users WHERE id > 1 ORDER BY RAND() LIMIT 200");
$uids=array_column($userIds,'id');

// Image URLs - Vietnamese scenery + shipper related from Unsplash
$images=[
'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&h=400&fit=crop', // Saigon street
'https://images.unsplash.com/photo-1555921015-5532091f6026?w=600&h=400&fit=crop', // Vietnam motorbike
'https://images.unsplash.com/photo-1528127269322-539801943592?w=600&h=400&fit=crop', // Ha Long Bay
'https://images.unsplash.com/photo-1557750255-c76072a7aad1?w=600&h=400&fit=crop', // Hanoi street
'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=600&h=400&fit=crop', // Vietnam river
'https://images.unsplash.com/photo-1558862107-d49ef2a04d72?w=600&h=400&fit=crop', // Saigon skyline
'https://images.unsplash.com/photo-1504457047772-27faf1c00561?w=600&h=400&fit=crop', // Vietnamese food
'https://images.unsplash.com/photo-1535581652167-3a26c90588cd?w=600&h=400&fit=crop', // Motorbike street
'https://images.unsplash.com/photo-1509030450996-dd1a26dda07a?w=600&h=400&fit=crop', // Vietnam market
'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?w=600&h=400&fit=crop', // Street food
'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=600&h=400&fit=crop', // Ho Chi Minh City
'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600&h=400&fit=crop', // Vietnam landscape
'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&h=400&fit=crop', // Mountain road
'https://images.unsplash.com/photo-1549180030-48bf079c2994?w=600&h=400&fit=crop', // Vietnam temple
'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&h=400&fit=crop', // Delivery
'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop', // Restaurant
'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop', // Scooter
'https://images.unsplash.com/photo-1562077772-3bd90f85a0ed?w=600&h=400&fit=crop', // Vietnam rice field
'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=600&h=400&fit=crop', // Vietnam beach
'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=600&h=400&fit=crop', // Road journey
];

$types=['post','review','question','tip','discussion'];
$inserted=0;

// Download images to server
$imgDir='/home/nhshiw2j/public_html/uploads/posts/';
$savedImgs=[];
foreach($images as $idx=>$url){
    $fn='seed_vn_'.($idx+1).'.jpg';
    $path=$imgDir.$fn;
    if(!file_exists($path)){
        $img=@file_get_contents($url);
        if($img){file_put_contents($path,$img);$savedImgs[]='/uploads/posts/'.$fn;}
    }else{
        $savedImgs[]='/uploads/posts/'.$fn;
    }
}
echo "Downloaded ".count($savedImgs)." images\n";

$pdo->beginTransaction();
try{
foreach($contents as $i=>$row){
    $content=$row[0];
    $type=$row[1];
    $ship=$row[2];
    $uid=$uids[array_rand($uids)];
    $prov=$provinces[array_rand($provinces)];
    $dist=$prov==='Hồ Chí Minh'?$districts_hcm[array_rand($districts_hcm)]:($prov==='Hà Nội'?$districts_hn[array_rand($districts_hn)]:'');
    
    // 70% of posts get images
    $imgJson=null;
    if($i<35 && count($savedImgs)>0){
        $imgJson=json_encode([$savedImgs[$i % count($savedImgs)]]);
    }
    
    $likes=rand(3,120);
    $comments=rand(0,25);
    $shares=rand(0,15);
    $hoursAgo=rand(1,720);
    $createdAt=date('Y-m-d H:i:s',time()-$hoursAgo*3600);
    
    $pdo->prepare("INSERT INTO posts (user_id,content,type,images,likes_count,comments_count,shares_count,province,district,shipping_company,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$uid,$content,$type,$imgJson,$likes,$comments,$shares,$prov,$dist,$ship,'active',$createdAt]);
    $inserted++;
}
$pdo->commit();
echo "Batch 1: Inserted $inserted posts\n";

// Add interactions (likes + comments)
$postIds=$d->fetchAll("SELECT id FROM posts ORDER BY id DESC LIMIT 50");
$cmtContents=[
    'Đúng rồi bạn, mình cũng gặp y chang!',
    'Cảm ơn chia sẻ, rất hữu ích 👍',
    'Shipper đoàn kết! 💪',
    'Khu vực mình cũng vậy',
    'Hay quá, bookmark lại',
    'Chia sẻ kinh nghiệm thêm đi bạn',
    'Giao hàng cẩn thận nhé ae',
    'Mình ship 3 năm rồi, confirm đúng!',
    'Cố lên bạn! 🔥',
    'Like mạnh!',
    'Giao hết đơn rồi nghỉ ngơi nha',
    'Thời tiết khó chịu thật',
    'Ae nào biết chỗ sửa xe rẻ ko?',
    'Haha đúng quá 😂',
    'Shipper tâm huyết đây!',
];
$cmtCount=0;$likeCount=0;
foreach($postIds as $p){
    $pid=$p['id'];
    // Add 2-8 likes per post
    $likers=array_rand(array_flip($uids),min(rand(2,8),count($uids)));
    if(!is_array($likers))$likers=[$likers];
    foreach($likers as $luid){
        $pdo->prepare("INSERT IGNORE INTO post_likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$luid]);
        $likeCount++;
    }
    // Add 1-4 comments per post
    $nc=rand(1,4);
    for($j=0;$j<$nc;$j++){
        $cuid=$uids[array_rand($uids)];
        $ct=$cmtContents[array_rand($cmtContents)];
        $pdo->prepare("INSERT INTO comments (post_id,user_id,content,`status`,created_at) VALUES (?,?,?,'active',?)")
            ->execute([$pid,$cuid,$ct,date('Y-m-d H:i:s',time()-rand(60,86400))]);
        $cmtCount++;
    }
}
echo "Added $likeCount likes, $cmtCount comments\n";
echo "DONE batch 1\n";
}catch(Exception $e){
    $pdo->rollback();
    echo "ERROR: ".$e->getMessage()."\n";
}
