<?php
// ShipperShop API v2 — Conversation Delivery ETA
// Tinh nang: Chia se thoi gian giao du kien
// Share and update estimated delivery times in conversations
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function cde_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cde_ok('OK',['etas'=>[]]);
    $key='conv_etas_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $etas=$row?json_decode($row['value'],true):[];
    foreach($etas as &$e){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($e['shipper_id']??0)]);
        if($u) $e['shipper_name']=$u['fullname'];
        $e['is_late']=strtotime($e['eta']??'')>0&&strtotime($e['eta']??'')<time()&&($e['status']??'')!=='delivered';
        $remaining=max(0,strtotime($e['eta']??'')-time());
        $e['remaining_min']=round($remaining/60);
    }unset($e);
    $activeEtas=array_values(array_filter($etas,function($e){return ($e['status']??'')!=='delivered';}));
    cde_ok('OK',['etas'=>$etas,'active'=>$activeEtas,'count'=>count($etas)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cde_ok('Missing');
    $key='conv_etas_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $etas=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $orderCode=trim($input['order_code']??'');
        $recipient=trim($input['recipient']??'');
        $eta=$input['eta']??'';
        $address=trim($input['address']??'');
        if(!$eta) cde_ok('Nhap thoi gian du kien');
        $maxId=0;foreach($etas as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        $etas[]=['id'=>$maxId+1,'shipper_id'=>$uid,'order_code'=>$orderCode,'recipient'=>$recipient,'eta'=>$eta,'address'=>$address,'status'=>'on_way','updates'=>[],'created_at'=>date('c')];
    }

    if($action==='update_eta'){
        $etaId=intval($input['eta_id']??0);$newEta=$input['new_eta']??'';$reason=trim($input['reason']??'');
        foreach($etas as &$e){
            if(intval($e['id']??0)===$etaId){
                $e['updates'][]=(['old'=>$e['eta'],'new'=>$newEta,'reason'=>$reason,'at'=>date('c')]);
                $e['eta']=$newEta;
            }
        }unset($e);
    }

    if($action==='delivered'){
        $etaId=intval($input['eta_id']??0);
        foreach($etas as &$e){if(intval($e['id']??0)===$etaId){$e['status']='delivered';$e['delivered_at']=date('c');}}unset($e);
    }

    if(count($etas)>50) $etas=array_slice($etas,-50);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($etas)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($etas))]);
    cde_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
