<?php
define('APP_ACCESS',true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo=db()->getConnection();

echo "=== map_pins columns ===\n";
$cols=$pdo->query("SHOW COLUMNS FROM map_pins")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== traffic_alerts columns ===\n";
$cols2=$pdo->query("SHOW COLUMNS FROM traffic_alerts")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols2 as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== traffic_alerts with lat/lng ===\n";
$cnt=$pdo->query("SELECT COUNT(*) as c FROM traffic_alerts WHERE lat IS NOT NULL AND lat!=0")->fetch();
echo "  With coords: {$cnt['c']}\n";
$cnt2=$pdo->query("SELECT COUNT(*) as c FROM traffic_alerts")->fetch();
echo "  Total: {$cnt2['c']}\n";

echo "\n=== Add rating columns to map_pins ===\n";
$existing=array_column($cols,'Field');
$adds=[
    ['rating','TINYINT(1) DEFAULT 0 COMMENT "1-5 stars"'],
    ['difficulty','ENUM("easy","medium","hard") DEFAULT NULL'],
    ['tags','VARCHAR(500) DEFAULT NULL COMMENT "JSON array of tags"'],
    ['upvotes','INT DEFAULT 0'],
    ['downvotes','INT DEFAULT 0'],
];
foreach($adds as $a){
    if(!in_array($a[0],$existing)){
        try{$pdo->exec("ALTER TABLE map_pins ADD COLUMN `{$a[0]}` {$a[1]}");echo "  OK: {$a[0]}\n";}
        catch(Throwable $e){echo "  ERR: {$a[0]} - {$e->getMessage()}\n";}
    } else echo "  SKIP: {$a[0]}\n";
}

// Seed sample traffic alerts with coordinates if none exist
if($cnt2['c']==0){
    echo "\n=== Seeding traffic alerts ===\n";
    $alerts=[
        [21.0285,105.8542,'traffic','high','Kẹt xe Nguyễn Huệ','Kẹt từ Lê Lợi đến Trần Hưng Đạo, né đi vòng',2],
        [21.0130,105.8450,'weather','medium','Ngập đường Lý Thường Kiệt','Ngập khoảng 20cm sau mưa lớn',3],
        [10.7769,106.7009,'traffic','critical','Tai nạn Điện Biên Phủ Q3','2 xe máy, đã có CSGT xử lý, kẹt 1km',2],
        [10.8020,106.6650,'warning','low','Công trình đào đường Cách Mạng T8','Đào đường 1 làn, đi chậm',6],
        [16.0544,108.2022,'terrain','medium','Đường sụt lún Nguyễn Văn Linh ĐN','Ổ gà lớn trước Vincom, cẩn thận',10],
    ];
    foreach($alerts as $a){
        $pdo->exec("INSERT INTO traffic_alerts (lat,lng,category,severity,title,description,user_id,`status`,created_at,expires_at) VALUES ({$a[0]},{$a[1]},'{$a[2]}','{$a[3]}','{$a[4]}','{$a[5]}',{$a[6]},'active',NOW(),DATE_ADD(NOW(),INTERVAL 2 HOUR))");
    }
    echo "  Seeded 5 alerts\n";
}

// Seed sample map pins if none exist
$pinCnt=$pdo->query("SELECT COUNT(*) as c FROM map_pins")->fetch();
if($pinCnt['c']==0){
    echo "\n=== Seeding map pins ===\n";
    $pins=[
        [21.0285,105.8542,'delivery','Kho hàng GHTK Cầu Giấy','Tầng 1, cổng B. Gọi trước 10p','Đường Duy Tân, Cầu Giấy',2,4,'medium'],
        [21.0350,105.8340,'favorite','Quán cà phê shipper Mỹ Đình','Wifi mạnh, sạc free, đồ uống rẻ','Phạm Hùng, Mỹ Đình',3,5,'easy'],
        [10.7769,106.7009,'warning','Chung cư Sunrise Q7 khó giao','Bảo vệ không cho lên, phải gọi KH xuống','Nguyễn Hữu Thọ, Q7',2,2,'hard'],
        [10.8020,106.6650,'note','Điểm tập kết đơn Quận Tân Bình','Shipper hay đợi đơn ở đây, gần nhiều shop','Cộng Hòa, Tân Bình',6,4,'easy'],
        [16.0544,108.2022,'delivery','Kho J&T Đà Nẵng','Nhận đơn 7h-17h, T2-T7','Nguyễn Văn Linh, Đà Nẵng',10,3,'medium'],
        [21.0200,105.8600,'note','WC công cộng Hoàn Kiếm','Miễn phí, sạch sẽ','Gần Bờ Hồ',3,0,NULL],
        [10.7600,106.6800,'favorite','Trạm sạc xe điện Q1','Sạc nhanh VinFast, 5k/lần','Pasteur, Q1',8,5,'easy'],
    ];
    foreach($pins as $p){
        $diff=$p[8]?"'{$p[8]}'":"NULL";
        $pdo->exec("INSERT INTO map_pins (lat,lng,pin_type,title,description,address,user_id,rating,difficulty) VALUES ({$p[0]},{$p[1]},'{$p[2]}','{$p[3]}','{$p[4]}','{$p[5]}',{$p[6]},{$p[7]},$diff)");
    }
    echo "  Seeded 7 pins\n";
}
