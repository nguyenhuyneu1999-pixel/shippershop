<?php
// ShipperShop API v2 — Admin Error Tracker
// Track PHP errors, API failures, client-side errors
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function et_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function et_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') et_fail('Admin only',403);

    $data=cache_remember('error_tracker', function() use($d) {
        // Client errors from settings
        $key='client_errors';
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $clientErrors=$row?json_decode($row['value'],true):[];

        // Audit log errors
        $apiErrors=$d->fetchAll("SELECT action,details,created_at FROM audit_log WHERE action LIKE '%error%' ORDER BY created_at DESC LIMIT 20");

        // Error rate by hour
        $hourly=$d->fetchAll("SELECT HOUR(created_at) as h, COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(created_at) ORDER BY h");

        $total24h=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
        $totalAll=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%'")['c']);

        return ['client_errors'=>array_slice($clientErrors,-20),'api_errors'=>$apiErrors,'hourly'=>$hourly,'total_24h'=>$total24h,'total_all'=>$totalAll];
    }, 120);
    et_ok('OK',$data);
}

// POST: report client error
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $error=['message'=>trim($input['message']??''),'url'=>trim($input['url']??''),'stack'=>trim($input['stack']??''),'ua'=>$_SERVER['HTTP_USER_AGENT']??'','ip'=>$_SERVER['REMOTE_ADDR']??'','reported_at'=>date('c')];

    $key='client_errors';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $errors=$row?json_decode($row['value'],true):[];
    $errors[]=$error;
    if(count($errors)>200) $errors=array_slice($errors,-200);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($errors),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($errors)]);
    et_ok('Logged');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
