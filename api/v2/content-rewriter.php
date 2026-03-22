<?php
// ShipperShop API v2 — Content Rewriter
// Suggest improved versions of post content (template-based)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$STYLES=[
    ['id'=>'professional','name'=>'Chuyen nghiep','icon'=>'💼','prefix'=>'[Cap nhat] ','suffix'=>"\n\n📍 Khu vuc hoat dong: [khu vuc]\n📞 Lien he: [SDT]"],
    ['id'=>'friendly','name'=>'Than thien','icon'=>'😊','prefix'=>'Hey anh em shipper! 👋\n\n','suffix'=>"\n\nAi co kinh nghiem tuong tu chia se nhe! 💪\n#shipper #congdong"],
    ['id'=>'urgent','name'=>'Gap','icon'=>'🔴','prefix'=>'⚡ KHAN CAP:\n\n','suffix'=>"\n\n⏰ Can xu ly NGAY! Lien he gap!"],
    ['id'=>'story','name'=>'Ke chuyen','icon'=>'📖','prefix'=>'📖 Chia se kinh nghiem:\n\n','suffix'=>"\n\nBai hoc rut ra: [ket luan]\n\nAnh em co gap truong hop nay chua? 🤔\n#kinhnghiem"],
    ['id'=>'review','name'=>'Danh gia','icon'=>'⭐','prefix'=>'⭐ REVIEW:\n\n','suffix'=>"\n\n👍 Uu diem: [list]\n👎 Nhuoc diem: [list]\n\nDiem: ⭐⭐⭐⭐ [X/5]\n#review #danhgia"],
];

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
$style=$_GET['style']??'';

if(!$text){echo json_encode(['success'=>true,'data'=>['styles'=>$STYLES]],JSON_UNESCAPED_UNICODE);exit;}

$results=[];
$targetStyles=$style?array_filter($STYLES,function($s) use($style){return $s['id']===$style;}):$STYLES;

foreach($targetStyles as $s){
    $rewritten=$s['prefix'].$text.$s['suffix'];
    // Add hashtags if missing
    if(strpos($rewritten,'#')===false) $rewritten.="\n\n#shippershop";
    $results[]=['style'=>$s['id'],'name'=>$s['name'],'icon'=>$s['icon'],'text'=>$rewritten,'char_count'=>mb_strlen($rewritten)];
}

echo json_encode(['success'=>true,'data'=>['original'=>$text,'rewritten'=>$results,'styles'=>$STYLES]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
