<?php
// ShipperShop API v2 — Conversation Reminder
// Set reminders for follow-up messages or delivery confirmations
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
function crm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
try {
$uid=require_auth();
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) crm2_ok('OK',['reminders'=>[]]);
    $key='conv_reminders_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reminders=$row?json_decode($row['value'],true):[];
    foreach($reminders as &$r){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($r['user_id']??0)]);
        if($u) $r['user_name']=$u['fullname'];
        $r['is_past']=strtotime($r['remind_at']??'')<time();
        $r['remaining']=max(0,strtotime($r['remind_at']??'')-time());
    }unset($r);
    usort($reminders,function($a,$b){return intval($a['is_past'])-intval($b['is_past']);});
    $active=count(array_filter($reminders,function($r){return !$r['is_past'];}));
    crm2_ok('OK',['reminders'=>$reminders,'count'=>count($reminders),'active'=>$active]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) crm2_ok('Missing');
    $key='conv_reminders_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reminders=$row?json_decode($row['value'],true):[];
    if(!$action||$action==='add'){
        $text=trim($input['text']??'');$remindAt=$input['remind_at']??'';
        if(!$text||!$remindAt) crm2_ok('Nhap noi dung va thoi gian');
        $maxId=0;foreach($reminders as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
        $reminders[]=['id'=>$maxId+1,'text'=>$text,'remind_at'=>$remindAt,'user_id'=>$uid,'created_at'=>date('c')];
        if(count($reminders)>30) crm2_ok('Toi da 30');
    }
    if($action==='delete'){$rid=intval($input['reminder_id']??0);$reminders=array_values(array_filter($reminders,function($r) use($rid){return intval($r['id']??0)!==$rid;}));}
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($reminders)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($reminders))]);
    crm2_ok('OK!');
}
} catch (\Throwable $e) {echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
