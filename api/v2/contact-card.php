<?php
// ShipperShop API v2 — Contact Card
// Share contact info as a structured card in conversations
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

function cc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='contact_card_'.$uid;

// GET: my contact card
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??$uid);
    $cardKey='contact_card_'.$userId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$cardKey]);
    $card=$row?json_decode($row['value'],true):null;
    if(!$card){
        $u=$d->fetchOne("SELECT fullname,avatar,bio,shipping_company FROM users WHERE id=?",[$userId]);
        $card=['name'=>$u['fullname']??'','company'=>$u['shipping_company']??'','phone'=>'','email'=>'','address'=>'','note'=>''];
    }
    cc_ok('OK',$card);
}

// POST: save contact card
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $card=['name'=>trim($input['name']??''),'company'=>trim($input['company']??''),'phone'=>trim($input['phone']??''),'email'=>trim($input['email']??''),'address'=>trim($input['address']??''),'note'=>trim($input['note']??''),'updated_at'=>date('c')];

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($card),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($card)]);
    cc_ok('Da luu the lien lac!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
