<?php
// ShipperShop API v2 — AI Content Suggest
// Template-based content suggestions for shippers
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$SUGGESTIONS=[
    ['id'=>1,'category'=>'morning','title'=>'Chao buoi sang','template'=>"☀️ Chao buoi sang anh em shipper!\n\nHom nay minh bat dau tu [khu vuc], du kien giao [X] don.\nThoi tiet [tot/xau], moi nguoi chu y [an toan/ao mua]!\n\nChuc anh em ngay moi nang luong! 💪\n\n#sangsom #shipper",'time'=>'06:00-09:00'],
    ['id'=>2,'category'=>'delivery','title'=>'Cap nhat giao hang','template'=>"📦 Cap nhat giao hang hom nay:\n\n✅ Da giao: [X] don\n⏳ Dang giao: [Y] don\n❌ That bai: [Z] don\n\n📍 Khu vuc: [tinh/thanh]\n💰 Doanh thu tam: [so tien]d\n\n#capnhat #giaohang",'time'=>'12:00-14:00'],
    ['id'=>3,'category'=>'tip','title'=>'Chia se meo','template'=>"💡 Meo giao hang hieu qua:\n\n[Meo 1]: ...\n[Meo 2]: ...\n[Meo 3]: ...\n\nAnh em co meo gi them khong? Chia se ben duoi nhe! 👇\n\n#kinhnghiem #meo #shipper",'time'=>'anytime'],
    ['id'=>4,'category'=>'evening','title'=>'Tong ket ngay','template'=>"🌙 Tong ket ngay [ngay/thang]:\n\n📦 Tong don: [X]\n✅ Thanh cong: [Y] ([Z]%)\n💰 Thu nhap: [so tien]d\n⛽ Xang: [so tien]d\n📍 Quang duong: [km] km\n\nCam xuc: [vui/met/binh thuong]\n\n#tongket #shipper",'time'=>'19:00-22:00'],
    ['id'=>5,'category'=>'question','title'=>'Hoi cong dong','template'=>"❓ Hoi anh em shipper:\n\n[Cau hoi cua ban]\n\nContext: Minh dang [tinh huong] o [khu vuc], chua biet [van de].\n\nCam on anh em giup do! 🙏\n\n#hoidap #congdong",'time'=>'anytime'],
    ['id'=>6,'category'=>'review','title'=>'Review hang','template'=>"⭐ REVIEW: [Ten hang van chuyen]\n\nDiem danh gia: ⭐⭐⭐⭐⭐ [X/5]\n\n👍 Uu diem:\n- [Diem 1]\n- [Diem 2]\n\n👎 Nhuoc diem:\n- [Diem 1]\n\n💬 Ket luan: [Tom tat]\n\n#review #danhgia #[hang]",'time'=>'anytime'],
];

try {

$action=$_GET['action']??'';
$category=$_GET['category']??'';

if(!$action||$action==='list'){
    $result=$SUGGESTIONS;
    if($category) $result=array_values(array_filter($result,function($s) use($category){return $s['category']===$category;}));

    // Add time relevance
    $hour=intval(date('H'));
    foreach($result as &$s){
        $s['is_relevant']=false;
        if($s['time']==='anytime') $s['is_relevant']=true;
        elseif(preg_match('/(\d+):00-(\d+):00/',$s['time'],$m)){
            if($hour>=intval($m[1])&&$hour<=intval($m[2])) $s['is_relevant']=true;
        }
    }unset($s);
    // Sort: relevant first
    usort($result,function($a,$b){return intval($b['is_relevant'])-intval($a['is_relevant']);});

    $categories=[['id'=>'morning','name'=>'Sang','icon'=>'☀️'],['id'=>'delivery','name'=>'Giao hang','icon'=>'📦'],['id'=>'tip','name'=>'Meo','icon'=>'💡'],['id'=>'evening','name'=>'Toi','icon'=>'🌙'],['id'=>'question','name'=>'Hoi dap','icon'=>'❓'],['id'=>'review','name'=>'Danh gia','icon'=>'⭐']];

    echo json_encode(['success'=>true,'data'=>['suggestions'=>$result,'categories'=>$categories,'count'=>count($result),'current_hour'=>$hour]],JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
