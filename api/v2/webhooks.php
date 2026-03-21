<?php
// ShipperShop API v2 — Webhook Events
// Register webhooks to receive events (new post, new user, payment, etc.)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';
$VALID_EVENTS=['post.created','post.liked','user.registered','user.followed','payment.completed','message.sent','story.created','group.joined','report.created'];

function wh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function wh_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: list available events (no auth needed)
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='events'){
    wh_ok('OK',$VALID_EVENTS);
}

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
$isAdmin=$admin&&$admin['role']==='admin';

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List registered webhooks (admin)
    if(!$action||$action==='list'){
        if(!$isAdmin) wh_fail('Admin only',403);
        $hooks=$d->fetchAll("SELECT * FROM settings WHERE `key` LIKE 'webhook_%' ORDER BY `key`");
        $result=[];
        foreach($hooks as $h){
            $data=json_decode($h['value'],true);
            $result[]=['id'=>$h['id'],'key'=>$h['key'],'url'=>$data['url']??'','events'=>$data['events']??[],'active'=>$data['active']??true,'created'=>$data['created']??''];
        }
        wh_ok('OK',$result);
    }

    wh_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!$isAdmin) wh_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);

    // Register webhook
    if($action==='register'){
        $url=trim($input['url']??'');
        $events=$input['events']??[];
        if(!$url||!filter_var($url,FILTER_VALIDATE_URL)) wh_fail('URL không hợp lệ');
        if(empty($events)) wh_fail('Chọn ít nhất 1 event');
        // Validate events
        foreach($events as $ev){if(!in_array($ev,$VALID_EVENTS)) wh_fail('Event không hợp lệ: '.$ev);}

        $key='webhook_'.substr(md5($url.time()),0,8);
        $secret=bin2hex(random_bytes(16));
        $data=json_encode(['url'=>$url,'events'=>$events,'secret'=>$secret,'active'=>true,'created'=>date('Y-m-d H:i:s')]);
        $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,$data]);
        wh_ok('Đã đăng ký webhook',['key'=>$key,'secret'=>$secret]);
    }

    // Delete webhook
    if($action==='delete'){
        $key=trim($input['key']??'');
        if(!$key) wh_fail('Missing key');
        $d->query("DELETE FROM settings WHERE `key`=?",[$key]);
        wh_ok('Đã xóa');
    }

    // Test webhook (send test ping)
    if($action==='test'){
        $key=trim($input['key']??'');
        $hook=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        if(!$hook) wh_fail('Webhook not found');
        $data=json_decode($hook['value'],true);
        $url=$data['url']??'';
        $payload=json_encode(['event'=>'test.ping','data'=>['message'=>'Hello from ShipperShop!','timestamp'=>date('c')]]);
        $sig=hash_hmac('sha256',$payload,$data['secret']??'');
        $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-ShipperShop-Signature: ".$sig."\r\n",'content'=>$payload,'timeout'=>10,'ignore_errors'=>true]]);
        $resp=@file_get_contents($url,false,$ctx);
        $code=0;
        if(isset($http_response_header)){foreach($http_response_header as $h){if(preg_match('/^HTTP\/\S+\s+(\d+)/',$h,$m))$code=intval($m[1]);}}
        wh_ok('Test sent',['url'=>$url,'response_code'=>$code,'response'=>substr($resp??'',0,200)]);
    }

    wh_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
