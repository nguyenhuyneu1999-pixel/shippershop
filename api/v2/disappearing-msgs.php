<?php
// ShipperShop API v2 — Disappearing Messages
// Self-destructing messages with configurable TTL
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function dm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: conversation disappearing settings + active ephemeral messages
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) dm2_ok('OK',['enabled'=>false]);

    $settingsKey='disappear_settings_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$settingsKey]);
    $settings=$row?json_decode($row['value'],true):['enabled'=>false,'ttl_hours'=>24];

    $msgsKey='disappear_msgs_'.$convId;
    $mrow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$msgsKey]);
    $msgs=$mrow?json_decode($mrow['value'],true):[];

    // Filter expired
    $active=array_values(array_filter($msgs,function($m){return strtotime($m['expires_at']??'')>time();}));
    // Clean up if changed
    if(count($active)!==count($msgs)){
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($active),$msgsKey]);
    }

    dm2_ok('OK',['settings'=>$settings,'messages'=>$active,'count'=>count($active)]);
}

// POST: configure or send disappearing message
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    if(!$action||$action==='settings'){
        $convId=intval($input['conversation_id']??0);
        if(!$convId) dm2_ok('Missing conversation_id');
        $settingsKey='disappear_settings_'.$convId;
        $settings=['enabled'=>!empty($input['enabled']),'ttl_hours'=>max(1,min(168,intval($input['ttl_hours']??24)))];
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$settingsKey]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($settings),$settingsKey]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$settingsKey,json_encode($settings)]);
        dm2_ok('Da cap nhat!');
    }

    if($action==='send'){
        $convId=intval($input['conversation_id']??0);
        $content=trim($input['content']??'');
        $ttlHours=max(1,min(168,intval($input['ttl_hours']??24)));
        if(!$convId||!$content) dm2_ok('Thieu du lieu');

        $msgsKey='disappear_msgs_'.$convId;
        $mrow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$msgsKey]);
        $msgs=$mrow?json_decode($mrow['value'],true):[];
        $maxId=0;foreach($msgs as $m){if(intval($m['id']??0)>$maxId)$maxId=intval($m['id']);}
        $msgs[]=['id'=>$maxId+1,'sender_id'=>$uid,'content'=>$content,'created_at'=>date('c'),'expires_at'=>date('c',time()+$ttlHours*3600),'ttl_hours'=>$ttlHours];

        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$msgsKey]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($msgs),$msgsKey]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$msgsKey,json_encode($msgs)]);
        dm2_ok('Da gui tin tu huy!',['expires_at'=>date('c',time()+$ttlHours*3600)]);
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
