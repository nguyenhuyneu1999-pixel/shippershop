<?php
define('APP_ACCESS',true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo=db()->getConnection();

// 1. Add rating columns to map_pins
$cols=array_column($pdo->query("SHOW COLUMNS FROM map_pins")->fetchAll(PDO::FETCH_ASSOC),'Field');
$adds=[
    ['rating','TINYINT DEFAULT 0'],
    ['difficulty',"ENUM('easy','medium','hard') DEFAULT NULL"],
    ['tags','VARCHAR(500) DEFAULT NULL'],
    ['upvotes','INT DEFAULT 0'],
    ['downvotes','INT DEFAULT 0'],
];
foreach($adds as $a){
    if(!in_array($a[0],$cols)){
        try{$pdo->exec("ALTER TABLE map_pins ADD COLUMN `{$a[0]}` {$a[1]}");echo "OK: map_pins.{$a[0]}\n";}
        catch(Throwable $e){echo "ERR: {$a[0]} - {$e->getMessage()}\n";}
    } else echo "SKIP: {$a[0]}\n";
}

// 2. Check traffic_alerts - use correct column names (latitude/longitude)
$cnt=$pdo->query("SELECT COUNT(*) as c FROM traffic_alerts WHERE latitude IS NOT NULL AND latitude!=0")->fetch();
echo "\nTraffic with coords: {$cnt['c']}\n";
$cnt2=$pdo->query("SELECT COUNT(*) as c FROM traffic_alerts")->fetch();
echo "Traffic total: {$cnt2['c']}\n";

// 3. Seed traffic alerts with lat/lng if empty
if($cnt2['c']==0){
    echo "\nSeeding traffic alerts...\n";
    $alerts=[
        [21.0285,105.8542,'traffic','high','Kẹt xe nghiêm trọng đường Nguyễn Huệ, từ Lê Lợi đến Trần Hưng Đạo. Né đi vòng Hai Bà Trưng.',2],
        [21.0130,105.8450,'weather','medium','Ngập đường Lý Thường Kiệt khoảng 20cm sau mưa lớn. Xe máy đi chậm.',3],
        [10.7769,106.7009,'traffic','critical','Tai nạn 2 xe máy trên Điện Biên Phủ Q3. CSGT đang xử lý, kẹt 1km.',2],
        [10.8020,106.6650,'warning','low','Công trình đào đường Cách Mạng Tháng 8 gần Tân Bình. Đi chậm 1 làn.',6],
        [16.0544,108.2022,'terrain','medium','Ổ gà lớn đường Nguyễn Văn Linh trước Vincom Đà Nẵng. Cẩn thận!',10],
    ];
    foreach($alerts as $a){
        $pdo->prepare("INSERT INTO traffic_alerts (latitude,longitude,category,severity,content,user_id,`status`,created_at,expires_at) VALUES (?,?,?,?,?,?,?,NOW(),DATE_ADD(NOW(),INTERVAL 2 HOUR))")
            ->execute([$a[0],$a[1],$a[2],$a[3],$a[4],$a[5],'active']);
    }
    echo "Seeded 5 alerts\n";
}

// 4. Seed map pins with rating/difficulty
$pinCnt=$pdo->query("SELECT COUNT(*) as c FROM map_pins")->fetch();
echo "\nPins total: {$pinCnt['c']}\n";
if($pinCnt['c']==0){
    echo "Seeding map pins...\n";
    $pins=[
        [21.0285,105.8542,'delivery','Kho hàng GHTK Cầu Giấy','Tầng 1 cổng B. Gọi trước 10p. Nhận 7h-17h T2-T7','Duy Tân, Cầu Giấy, Hà Nội',2,4,'medium'],
        [21.0350,105.8340,'favorite','Quán cà phê shipper Mỹ Đình','Wifi mạnh, sạc free, trà đá 5k. Shipper hay tụ tập','Phạm Hùng, Mỹ Đình',3,5,'easy'],
        [10.7769,106.7009,'warning','Chung cư Sunrise City Q7','Bảo vệ KHÔNG cho lên. Phải gọi KH xuống lấy. Đỗ xe tầng hầm B1','Nguyễn Hữu Thọ, Q7, HCM',2,2,'hard'],
        [10.8020,106.6650,'note','Điểm tập kết đơn Tân Bình','Shipper GHTK/GHN hay đợi đơn ở đây, gần nhiều shop online','Cộng Hòa, Tân Bình, HCM',6,4,'easy'],
        [16.0544,108.2022,'delivery','Kho J&T Express Đà Nẵng','Nhận đơn 7h-17h T2-T7. Có bãi đỗ xe rộng','Nguyễn Văn Linh, Hải Châu, ĐN',10,3,'medium'],
        [21.0200,105.8600,'note','WC công cộng gần Bờ Hồ','Miễn phí, tương đối sạch. Mở 6h-22h','Đinh Tiên Hoàng, Hoàn Kiếm',3,0,NULL],
        [10.7600,106.6800,'favorite','Trạm sạc xe điện VinFast Q1','Sạc nhanh 30 phút, 5k/lần. Có ghế ngồi đợi','Pasteur, Q1, HCM',8,5,'easy'],
        [21.0100,105.8200,'warning','Ngõ 42 Trần Cung rất hẹp','Xe tải không vào được, chỉ xe máy. Cua gấp đầu ngõ','Trần Cung, Cầu Giấy, HN',15,1,'hard'],
        [10.8500,106.6300,'delivery','Hub SPX Bình Tân','Hub lớn, nhiều đơn. Xếp hàng lâu giờ cao điểm 8-10h sáng','Kinh Dương Vương, Bình Tân',20,3,'medium'],
    ];
    foreach($pins as $p){
        $diff=$p[8]?"'{$p[8]}'":"NULL";
        $st=$pdo->prepare("INSERT INTO map_pins (lat,lng,pin_type,title,description,address,user_id,rating,difficulty) VALUES (?,?,?,?,?,?,?,?,$diff)");
        $st->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7]]);
    }
    echo "Seeded 9 pins\n";
}
