<?php
// ShipperShop API v2 — Smart Reply
// Context-aware quick reply suggestions for conversations
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

$REPLIES=[
    ['pattern'=>['cam on','thank'],'replies'=>['Khong co gi a!','Da, rat vui duoc giup!','Cam on anh/chi da tin tuong!']],
    ['pattern'=>['bao nhieu','gia'],'replies'=>['De em bao gia nhe','Gia giao hang phu thuoc khoang cach a','Em gui bang gia cho anh/chi']],
    ['pattern'=>['bao gio','khi nao','may gio'],'replies'=>['Khoang 30 phut nua a','Em dang tren duong, den ngay','Se giao trong hom nay a']],
    ['pattern'=>['ok','duoc','dong y'],'replies'=>['Da, em ghi nhan a!','Ok anh/chi, em xu ly ngay','Cam on, em bat dau lam luon!']],
    ['pattern'=>['huy','khong can','thoi'],'replies'=>['Da, em huy don ngay a','Anh/chi xac nhan huy nhe?','Da ghi nhan, em se huy']],
    ['pattern'=>['dia chi','o dau','cho nao'],'replies'=>['Anh/chi gui dia chi cu the giup em','Em can dia chi chinh xac nhe','Vui long gui pin location a']],
    ['pattern'=>['hello','chao','hi','xin chao'],'replies'=>['Chao anh/chi! Em co the giup gi a?','Hi! Rat vui duoc phuc vu!','Xin chao, anh/chi can giao hang a?']],
    ['pattern'=>['gap','nhanh','som'],'replies'=>['Em se co giao som nhat a!','Dang tren duong, den ngay a!','Em uu tien don nay a']],
];

function sr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$lastMessage=trim($_GET['message']??'');

if(!$lastMessage){
    // Default replies
    sr_ok('OK',['replies'=>['Chao anh/chi!','Em dang san sang','Can giup gi a?'],'context'=>'default']);
}

$lower=mb_strtolower($lastMessage);
$matched=[];

foreach($REPLIES as $r){
    foreach($r['pattern'] as $p){
        if(mb_strpos($lower,$p)!==false){
            $matched=array_merge($matched,$r['replies']);
            break;
        }
    }
}

$matched=array_unique($matched);
if(empty($matched)) $matched=['Da, em ghi nhan!','De em kiem tra nhe','Em xu ly ngay a'];

sr_ok('OK',['replies'=>array_slice($matched,0,5),'context'=>'matched','input'=>$lastMessage]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
