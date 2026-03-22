<?php
// ShipperShop API v2 — Post Template Library
// Pre-built post templates for common shipper scenarios
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$TEMPLATES=[
    ['id'=>1,'cat'=>'delivery','title'=>'Bao cao giao hang','body'=>"📦 Hom nay da giao [X] don thanh cong!\n\n🏍️ Tuyen: [khu vuc]\n⏰ Thoi gian: [X]h\n💰 Thu nhap: [X]d\n\n#shipper #giaohang #[thanhpho]",'icon'=>'📦'],
    ['id'=>2,'cat'=>'delivery','title'=>'Tim dong giao','body'=>"🔍 Tim dong giao khu vuc [quan/huyen]!\n\nThoi gian: [sang/chieu/toi]\nHang: [GHTK/GHN/SPX...]\nLuong: [X] don/ngay\n\nAi co don lien he em nhe!\n📞 [SDT]\n#timdon #shipper",'icon'=>'🔍'],
    ['id'=>3,'cat'=>'tip','title'=>'Chia se kinh nghiem','body'=>"💡 Meo giao hang nhanh:\n\n1️⃣ [Meo 1]\n2️⃣ [Meo 2]\n3️⃣ [Meo 3]\n\nAnh em co meo gi them khong?\n#kinhnghiem #shipper",'icon'=>'💡'],
    ['id'=>4,'cat'=>'tip','title'=>'Review hang van chuyen','body'=>"⭐ Review [ten hang van chuyen]\n\n👍 Uu diem:\n- [diem tot 1]\n- [diem tot 2]\n\n👎 Nhuoc diem:\n- [diem xau 1]\n\nDiem: ⭐⭐⭐⭐ [X/5]\n#review #[tenhang]",'icon'=>'⭐'],
    ['id'=>5,'cat'=>'alert','title'=>'Canh bao duong','body'=>"⚠️ CANH BAO:\n\n📍 Dia diem: [dia chi cu the]\n🚧 Tinh trang: [mo ta]\n⏰ Tu: [thoi gian]\n\nAnh em tranh khu vuc nay!\n#canhbao #giaothong",'icon'=>'⚠️'],
    ['id'=>6,'cat'=>'social','title'=>'Tim ban dong hanh','body'=>"👋 Tim ban dong hanh giao hang!\n\nKhu vuc: [quan/huyen]\nThoi gian: [sang/chieu]\nHang: [GHTK/GHN...]\n\nDi chung tiet kiem xang, vui hon!\n#timban #shipper",'icon'=>'👋'],
    ['id'=>7,'cat'=>'sale','title'=>'Ban xe may','body'=>"🏍️ BAN XE [ten xe]\n\n📋 Thong so:\n- Nam: [nam]\n- Km da di: [X]km\n- Tinh trang: [moi/cu]\n\n💰 Gia: [X]d (thuong luong)\n📞 Lien he: [SDT]\n#banxe #shipper",'icon'=>'🏍️'],
    ['id'=>8,'cat'=>'social','title'=>'Hop mat shipper','body'=>"🎉 THONG BAO HOP MAT!\n\n📅 Ngay: [ngay]\n⏰ Gio: [gio]\n📍 Dia diem: [dia chi]\n\nNoi dung: [mo ta]\nMien phi tham gia!\n\n#hopmat #congdong",'icon'=>'🎉'],
];

$CATEGORIES=[['id'=>'delivery','name'=>'Giao hang','icon'=>'📦'],['id'=>'tip','name'=>'Kinh nghiem','icon'=>'💡'],['id'=>'alert','name'=>'Canh bao','icon'=>'⚠️'],['id'=>'social','name'=>'Cong dong','icon'=>'👋'],['id'=>'sale','name'=>'Mua ban','icon'=>'🏍️']];

try {
$cat=$_GET['category']??'';
$result=$TEMPLATES;
if($cat) $result=array_values(array_filter($result,function($t) use($cat){return $t['cat']===$cat;}));
echo json_encode(['success'=>true,'data'=>['templates'=>$result,'categories'=>$CATEGORIES,'count'=>count($result)]],JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
