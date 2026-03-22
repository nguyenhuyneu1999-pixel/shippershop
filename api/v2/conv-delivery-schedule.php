<?php
// ShipperShop API v2 — Conversation Delivery Schedule
// Schedule delivery windows and coordinate timing within conversations
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

function cds_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cds_ok('OK',['schedules'=>[]]);
    $key='conv_del_sched_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];
    foreach($schedules as &$s){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['shipper_id']??0)]);
        if($u) $s['shipper_name']=$u['fullname'];
        $s['is_past']=strtotime($s['end_time']??'')<time();
        $s['is_active']=strtotime($s['start_time']??'')<=time()&&strtotime($s['end_time']??'')>=time();
    }unset($s);
    $upcoming=array_values(array_filter($schedules,function($s){return !$s['is_past'];}));
    cds_ok('OK',['schedules'=>$schedules,'upcoming'=>$upcoming,'count'=>count($schedules)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cds_ok('Missing conversation_id');
    $key='conv_del_sched_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $startTime=$input['start_time']??'';
        $endTime=$input['end_time']??'';
        $area=trim($input['area']??'');
        $orderCount=intval($input['order_count']??0);
        $note=trim($input['note']??'');
        if(!$startTime||!$endTime) cds_ok('Nhap thoi gian');
        $maxId=0;foreach($schedules as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $schedules[]=['id'=>$maxId+1,'shipper_id'=>$uid,'start_time'=>$startTime,'end_time'=>$endTime,'area'=>$area,'order_count'=>$orderCount,'note'=>$note,'status'=>'scheduled','created_at'=>date('c')];
        if(count($schedules)>50) $schedules=array_slice($schedules,-50);
    }

    if($action==='complete'){
        $schedId=intval($input['schedule_id']??0);
        foreach($schedules as &$s){if(intval($s['id']??0)===$schedId){$s['status']='completed';$s['completed_at']=date('c');}}unset($s);
    }

    if($action==='cancel'){
        $schedId=intval($input['schedule_id']??0);
        foreach($schedules as &$s){if(intval($s['id']??0)===$schedId) $s['status']='cancelled';}unset($s);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($schedules)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($schedules))]);
    cds_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
