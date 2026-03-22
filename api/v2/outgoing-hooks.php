<?php
// ShipperShop API v2 — Outgoing Webhooks
// Admin registers webhook URLs, system fires events to them
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

function wh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function wh_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') wh_fail('Admin only',403);

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List registered webhooks (stored in settings)
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='outgoing_webhooks'");
    $hooks=$row?json_decode($row['value'],true):[];
    wh_ok('OK',['hooks'=>$hooks]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Register webhook
    if($action==='register'){
        $url=trim($input['url']??'');
        $events=$input['events']??['all'];
        $name=trim($input['name']??'');
        if(!$url||!filter_var($url,FILTER_VALIDATE_URL)) wh_fail('URL không hợp lệ');

        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='outgoing_webhooks'");
        $hooks=$row?json_decode($row['value'],true):[];
        $hooks[]=[ 'id'=>count($hooks)+1, 'name'=>$name?:$url, 'url'=>$url, 'events'=>$events, 'active'=>true, 'created_at'=>date('c')];

        $key='outgoing_webhooks';
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($hooks),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($hooks)]);

        wh_ok('Đã đăng ký webhook',['id'=>count($hooks)]);
    }

    // Remove webhook
    if($action==='remove'){
        $hookId=intval($input['hook_id']??0);
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='outgoing_webhooks'");
        $hooks=$row?json_decode($row['value'],true):[];
        $hooks=array_values(array_filter($hooks,function($h) use($hookId){return ($h['id']??0)!==$hookId;}));
        $d->query("UPDATE settings SET value=? WHERE `key`='outgoing_webhooks'",[json_encode($hooks)]);
        wh_ok('Đã xóa webhook');
    }

    // Test webhook
    if($action==='test'){
        $url=trim($input['url']??'');
        if(!$url) wh_fail('Missing URL');
        $payload=json_encode(['event'=>'test','timestamp'=>date('c'),'data'=>['message'=>'Hello from ShipperShop!']]);
        $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\n",'content'=>$payload,'timeout'=>5,'ignore_errors'=>true]]);
        $resp=@file_get_contents($url,false,$ctx);
        $code=0;
        if(isset($http_response_header)){foreach($http_response_header as $h){if(preg_match('/HTTP\/\S+\s+(\d+)/',$h,$m))$code=intval($m[1]);}}
        wh_ok('Test sent',['response_code'=>$code,'response'=>mb_substr($resp??'',0,200)]);
    }

    wh_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
