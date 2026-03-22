<?php
// ShipperShop API v2 — Post Templates Marketplace
// Browse/use/rate community-shared post templates
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function tm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$TEMPLATES=[
    ['id'=>1,'name'=>'Hoi dia chi','category'=>'question','content'=>"📍 Hoi dia chi: [ten nguoi nhan] o [khu vuc]\n\n📍 Khu vuc: [Tinh/Thanh], [Quan/Huyen]\n\nCam on anh em ho tro! 🙏\n\n#hoidiachi",'uses'=>342,'rating'=>4.5],
    ['id'=>2,'name'=>'Chia se kinh nghiem','category'=>'share','content'=>"💡 Kinh nghiem [chu de]\n\nSau [X] thang/nam lam shipper, minh rut ra duoc:\n\n1. ...\n2. ...\n3. ...\n\nAnh em co kinh nghiem gi them khong? 👇\n\n#kinhnghiem #shipper",'uses'=>215,'rating'=>4.7],
    ['id'=>3,'name'=>'Tuyen shipper','category'=>'recruit','content'=>"🚛 TUYEN SHIPPER [HANG VAN CHUYEN]\n\n📍 Khu vuc: [Tinh/Thanh]\n💰 Thu nhap: [X]tr - [Y]tr/thang\n🏍️ Yeu cau: [xe may/o to], CCCD, GPLX\n📞 Lien he: [SDT]\n\n#tuyendung #shipper #[tinh]",'uses'=>189,'rating'=>4.3],
    ['id'=>4,'name'=>'Canh bao giao thong','category'=>'alert','content'=>"⚠️ CANH BAO: [Loai canh bao]\n\n📍 Vi tri: [Duong/Khu vuc]\n⏰ Thoi gian: [gio]\n📝 Chi tiet: [mo ta]\n\n⚡ Moi nguoi tranh khu vuc nay!\n\n#canhbao #giaothong",'uses'=>156,'rating'=>4.1],
    ['id'=>5,'name'=>'Review hang van chuyen','category'=>'review','content'=>"⭐ REVIEW: [Ten hang van chuyen]\n\nDiem: ⭐⭐⭐⭐⭐ [X/5]\n\n✅ Uu diem:\n- ...\n\n❌ Nhuoc diem:\n- ...\n\n💬 Nhan xet chung: ...\n\n#review #[hang]",'uses'=>134,'rating'=>4.6],
    ['id'=>6,'name'=>'Tim don giao','category'=>'work','content'=>"📦 TIM DON GIAO\n\n📍 Khu vuc hoat dong: [Tinh/Thanh]\n🏍️ Phuong tien: [Xe may/Xe tai]\n⏰ Thoi gian: [Ca sang/Ca chieu/Full]\n💰 Nhan COD: [Co/Khong]\n📞 Lien he: [SDT]\n\n#timdon #shipper",'uses'=>98,'rating'=>4.2],
    ['id'=>7,'name'=>'Thong bao nghi','category'=>'notice','content'=>"📢 THONG BAO\n\n🗓️ Ngay [X] den [Y] minh tam nghi giao hang.\nLy do: [ly do]\n\nAnh em nao can ho tro khu vuc [khu vuc] lien he minh nhe!\n📞 [SDT]\n\nCam on moi nguoi! 🙏",'uses'=>76,'rating'=>3.9],
    ['id'=>8,'name'=>'Ket qua thang','category'=>'share','content'=>"📊 KET QUA THANG [X/2026]\n\n📦 Tong don: [X] don\n✅ Thanh cong: [Y] don ([Z]%)\n💰 Thu nhap: [X]tr\n⭐ Danh gia TB: [X]/5\n\n[Cam nghi/Bai hoc]\n\n#ketqua #thang[X]",'uses'=>67,'rating'=>4.4],
];

try {

if(!$action||$action==='list'){
    $cat=$_GET['category']??'';
    $result=$TEMPLATES;
    if($cat) $result=array_values(array_filter($result,function($t) use($cat){return $t['category']===$cat;}));
    $categories=[['id'=>'question','name'=>'Hoi dap','icon'=>'❓'],['id'=>'share','name'=>'Chia se','icon'=>'💡'],['id'=>'recruit','name'=>'Tuyen dung','icon'=>'🚛'],['id'=>'alert','name'=>'Canh bao','icon'=>'⚠️'],['id'=>'review','name'=>'Danh gia','icon'=>'⭐'],['id'=>'work','name'=>'Tim viec','icon'=>'📦'],['id'=>'notice','name'=>'Thong bao','icon'=>'📢']];
    tm_ok('OK',['templates'=>$result,'categories'=>$categories,'total'=>count($result)]);
}

if($action==='popular'){
    $sorted=$TEMPLATES;usort($sorted,function($a,$b){return $b['uses']-$a['uses'];});
    tm_ok('OK',array_slice($sorted,0,5));
}

tm_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
