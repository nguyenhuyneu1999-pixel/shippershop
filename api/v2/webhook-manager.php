<?php
// ShipperShop API v2 — Admin Webhook Manager
// Manage outgoing webhooks: endpoints, events, retry policy, logs
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

$EVENTS=['post.created','post.liked','post.commented','user.registered','user.subscribed','order.created','order.completed','payment.received'];

function wm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function wm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') wm2_fail('Admin only',403);

$key='webhook_manager';

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $hooks=$row?json_decode($row['value'],true):[];
    $activeCount=count(array_filter($hooks,function($h){return !empty($h['active']);}));
    wm2_ok('OK',['webhooks'=>$hooks,'events'=>$EVENTS,'count'=>count($hooks),'active'=>$activeCount]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $hooks=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $url=trim($input['url']??'');$events=$input['events']??[];$secret=trim($input['secret']??'');
        if(!$url||!filter_var($url,FILTER_VALIDATE_URL)) wm2_ok('URL khong hop le');
        $maxId=0;foreach($hooks as $h){if(intval($h['id']??0)>$maxId)$maxId=intval($h['id']);}
        $hooks[]=['id'=>$maxId+1,'url'=>$url,'events'=>$events,'secret'=>$secret?:bin2hex(random_bytes(16)),'active'=>true,'retries'=>3,'timeout'=>10,'created_at'=>date('c'),'last_triggered'=>null,'success_count'=>0,'fail_count'=>0];
        if(count($hooks)>20) wm2_ok('Toi da 20 webhooks');
    }

    if($action==='toggle'){
        $hookId=intval($input['webhook_id']??0);
        foreach($hooks as &$h){if(intval($h['id']??0)===$hookId) $h['active']=!($h['active']??true);}unset($h);
    }

    if($action==='delete'){
        $hookId=intval($input['webhook_id']??0);
        $hooks=array_values(array_filter($hooks,function($h) use($hookId){return intval($h['id']??0)!==$hookId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($hooks)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($hooks))]);
    wm2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
