<?php
// ShipperShop API v2 — Conversation Templates
// Pre-built message templates for common shipper conversations
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$TEMPLATES=[
    ['id'=>1,'cat'=>'delivery','title'=>'Xac nhan giao hang','msg'=>'Chao anh/chi, em da nhan don va dang tren duong giao. Du kien den noi trong [X] phut. Vui long de y dien thoai nhe!','icon'=>'📦'],
    ['id'=>2,'cat'=>'delivery','title'=>'Da giao thanh cong','msg'=>'Chao anh/chi, don hang da duoc giao thanh cong. Cam on anh/chi da su dung dich vu! Chuc anh/chi mot ngay tot lanh.','icon'=>'✅'],
    ['id'=>3,'cat'=>'delivery','title'=>'Khong lien lac duoc','msg'=>'Chao anh/chi, em da goi nhung khong lien lac duoc. Em se thu lai sau [X] phut. Neu anh/chi doc tin nay, vui long goi lai cho em nhe!','icon'=>'📞'],
    ['id'=>4,'cat'=>'issue','title'=>'Don bi cham tre','msg'=>'Chao anh/chi, xin loi vi don hang bi cham tre do [ly do]. Em dang co giao som nhat. Cam on anh/chi da thong cam!','icon'=>'⏰'],
    ['id'=>5,'cat'=>'issue','title'=>'Don bi huy','msg'=>'Chao anh/chi, rat tiec don hang phai bi huy do [ly do]. Em xin loi vi bat tien nay. Anh/chi co can ho tro gi them khong a?','icon'=>'❌'],
    ['id'=>6,'cat'=>'greeting','title'=>'Chao khach moi','msg'=>'Chao anh/chi! Em la shipper chuyen tuyen [khu vuc]. Rat vui duoc phuc vu anh/chi. Co gi can ho tro cu nhan tin nhe!','icon'=>'👋'],
    ['id'=>7,'cat'=>'payment','title'=>'Xac nhan COD','msg'=>'Chao anh/chi, don hang COD: [so tien]d. Anh/chi vui long chuan bi dung so tien nhe. Em se den trong [X] phut.','icon'=>'💰'],
    ['id'=>8,'cat'=>'payment','title'=>'Da thu tien','msg'=>'Chao anh/chi, em da thu [so tien]d COD. Cam on anh/chi! Chuc anh/chi vui ve.','icon'=>'💳'],
    ['id'=>9,'cat'=>'address','title'=>'Xac nhan dia chi','msg'=>'Chao anh/chi, em xac nhan dia chi giao: [dia chi]. Dung chua a? Neu co thay doi vui long bao em truoc [thoi gian].','icon'=>'📍'],
    ['id'=>10,'cat'=>'address','title'=>'Khong tim thay dia chi','msg'=>'Chao anh/chi, em khong tim thay dia chi [dia chi]. Anh/chi co the gui pin location hoac huong dan cu the hon khong a?','icon'=>'🗺️'],
];
$CATEGORIES=[['id'=>'delivery','name'=>'Giao hang','icon'=>'📦'],['id'=>'issue','name'=>'Van de','icon'=>'⚠️'],['id'=>'greeting','name'=>'Chao hoi','icon'=>'👋'],['id'=>'payment','name'=>'Thanh toan','icon'=>'💰'],['id'=>'address','name'=>'Dia chi','icon'=>'📍']];

try {

$cat=$_GET['category']??'';
$result=$TEMPLATES;
if($cat) $result=array_values(array_filter($result,function($t) use($cat){return $t['cat']===$cat;}));
echo json_encode(['success'=>true,'data'=>['templates'=>$result,'categories'=>$CATEGORIES,'count'=>count($result)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
