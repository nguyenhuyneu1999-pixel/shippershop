<?php
// ShipperShop API v2 — Conversation Auto-Label
// Auto-categorize conversations based on content keywords
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

$LABELS=[
    ['id'=>'order','name'=>'Don hang','icon'=>'📦','keywords'=>['don hang','giao hang','ship','cod','nhan hang','tra hang']],
    ['id'=>'work','name'=>'Cong viec','icon'=>'💼','keywords'=>['tuyen','ung tuyen','cv','phong van','luong','thu nhap']],
    ['id'=>'support','name'=>'Ho tro','icon'=>'🆘','keywords'=>['ho tro','giup','loi','khong duoc','bi sao','van de']],
    ['id'=>'social','name'=>'Xa hoi','icon'=>'😊','keywords'=>['chao','hi','hello','cam on','ok','vui','hen gap']],
    ['id'=>'urgent','name'=>'Khan cap','icon'=>'🔴','keywords'=>['gap','khan cap','ngay','lien','nhanh','som']],
    ['id'=>'payment','name'=>'Thanh toan','icon'=>'💰','keywords'=>['tien','thanh toan','chuyen khoan','nap','rut','vi']],
];

function cal_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: auto-label a conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cal_ok('OK',['labels'=>$LABELS,'suggested'=>[]]);

    // Get recent messages
    $msgs=$d->fetchAll("SELECT content FROM messages WHERE conversation_id=? ORDER BY created_at DESC LIMIT 20",[$convId]);
    $allText=mb_strtolower(implode(' ',array_column($msgs,'content')));

    $suggested=[];
    foreach($LABELS as $label){
        $score=0;
        foreach($label['keywords'] as $kw){
            if(mb_strpos($allText,$kw)!==false) $score++;
        }
        if($score>0) $suggested[]=['label'=>$label,'score'=>$score];
    }
    usort($suggested,function($a,$b){return $b['score']-$a['score'];});

    cal_ok('OK',['suggested'=>array_slice($suggested,0,3),'labels'=>$LABELS,'message_count'=>count($msgs)]);
}

// POST: manually set label
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $labelId=trim($input['label_id']??'');
    if(!$convId) cal_ok('Missing conversation_id');

    $key='conv_label_'.$uid.'_'.$convId;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(['label'=>$labelId,'set_at'=>date('c')]),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(['label'=>$labelId,'set_at'=>date('c')])]);
    cal_ok('Da gan nhan!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
