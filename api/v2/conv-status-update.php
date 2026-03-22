<?php
// ShipperShop API v2 — Conversation Status Updates
// Share delivery status updates (picked up, in transit, delivered) in conv
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
$STATUSES=[
    ['id'=>'picked_up','name'=>'Da lay hang','icon'=>'📦','color'=>'#7c3aed'],
    ['id'=>'in_transit','name'=>'Dang giao','icon'=>'🏍️','color'=>'#f59e0b'],
    ['id'=>'near_dest','name'=>'Gan den','icon'=>'📍','color'=>'#3b82f6'],
    ['id'=>'delivered','name'=>'Da giao','icon'=>'✅','color'=>'#22c55e'],
    ['id'=>'failed','name'=>'Giao that bai','icon'=>'❌','color'=>'#ef4444'],
    ['id'=>'returned','name'=>'Hoan tra','icon'=>'↩️','color'=>'#6b7280'],
];

function csu_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) csu_ok('OK',['updates'=>[],'statuses'=>$STATUSES]);
    $key='conv_status_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $updates=$row?json_decode($row['value'],true):[];
    foreach($updates as &$u){
        $usr=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($u['user_id']??0)]);
        if($usr){$u['fullname']=$usr['fullname'];$u['avatar']=$usr['avatar'];}
    }unset($u);
    csu_ok('OK',['updates'=>$updates,'statuses'=>$STATUSES,'count'=>count($updates)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $statusId=trim($input['status']??'');
    $note=trim($input['note']??'');
    if(!$convId||!$statusId) csu_ok('Missing data');
    $valid=array_column($STATUSES,'id');
    if(!in_array($statusId,$valid)) csu_ok('Invalid status');

    $key='conv_status_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $updates=$row?json_decode($row['value'],true):[];
    $maxId=0;foreach($updates as $u){if(intval($u['id']??0)>$maxId)$maxId=intval($u['id']);}
    array_unshift($updates,['id'=>$maxId+1,'user_id'=>$uid,'status'=>$statusId,'note'=>$note,'created_at'=>date('c')]);
    if(count($updates)>100) $updates=array_slice($updates,0,100);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($updates),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($updates)]);
    csu_ok('Da cap nhat trang thai!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
