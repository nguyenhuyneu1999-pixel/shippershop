<?php
// ShipperShop API v2 — Conversation Delivery Handoff
// Transfer delivery responsibility between shippers with tracking
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

$REASONS=['break'=>'Nghi ngoi','area'=>'Het khu vuc','vehicle'=>'Hong xe','personal'=>'Ca nhan','shift_end'=>'Het ca','emergency'=>'Khan cap','other'=>'Khac'];

function cdh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cdh_ok('OK',['handoffs'=>[],'reasons'=>$REASONS]);
    $key='conv_handoffs_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $handoffs=$row?json_decode($row['value'],true):[];
    foreach($handoffs as &$h){
        $from=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($h['from_id']??0)]);
        $to=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($h['to_id']??0)]);
        if($from) $h['from_name']=$from['fullname'];
        if($to) $h['to_name']=$to['fullname'];
    }unset($h);
    $pending=count(array_filter($handoffs,function($h){return ($h['status']??'')==='pending';}));
    cdh_ok('OK',['handoffs'=>$handoffs,'reasons'=>$REASONS,'count'=>count($handoffs),'pending'=>$pending]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cdh_ok('Missing conversation_id');
    $key='conv_handoffs_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $handoffs=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='request'){
        $toId=intval($input['to_id']??0);
        $reason=$input['reason']??'other';
        $orderCount=intval($input['order_count']??0);
        $notes=trim($input['notes']??'');
        if(!$toId) cdh_ok('Chon nguoi nhan ban giao');
        $maxId=0;foreach($handoffs as $h){if(intval($h['id']??0)>$maxId)$maxId=intval($h['id']);}
        $handoffs[]=['id'=>$maxId+1,'from_id'=>$uid,'to_id'=>$toId,'reason'=>$reason,'order_count'=>$orderCount,'notes'=>$notes,'status'=>'pending','created_at'=>date('c')];
    }

    if($action==='accept'){
        $hid=intval($input['handoff_id']??0);
        foreach($handoffs as &$h){if(intval($h['id']??0)===$hid&&intval($h['to_id']??0)===$uid){$h['status']='accepted';$h['accepted_at']=date('c');}}unset($h);
    }

    if($action==='reject'){
        $hid=intval($input['handoff_id']??0);
        foreach($handoffs as &$h){if(intval($h['id']??0)===$hid){$h['status']='rejected';$h['rejected_at']=date('c');}}unset($h);
    }

    if($action==='complete'){
        $hid=intval($input['handoff_id']??0);
        foreach($handoffs as &$h){if(intval($h['id']??0)===$hid){$h['status']='completed';$h['completed_at']=date('c');}}unset($h);
    }

    if(count($handoffs)>50) $handoffs=array_slice($handoffs,-50);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($handoffs)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($handoffs))]);
    cdh_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
