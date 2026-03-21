<?php
/**
 * ShipperShop API v2 — Analytics (page views tracking)
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$action=$_GET['action']??'';

// Track page view (POST, no auth required)
if($_SERVER['REQUEST_METHOD']==='POST'&&($action==='pageview'||!$action)){
    $input=json_decode(file_get_contents('php://input'),true);
    $page=trim($input['page']??'');
    if(!$page){echo json_encode(['success'=>false]);exit;}
    $uid=optional_auth();
    $ip=$_SERVER['REMOTE_ADDR']??'';
    $ref=$input['referrer']??$_SERVER['HTTP_REFERER']??'';
    try{
        $pdo=$d->getConnection();
        $pdo->prepare("INSERT INTO page_views (page,user_id,ip,referrer,created_at) VALUES (?,?,?,?,NOW())")->execute([mb_substr($page,0,100),$uid,$ip,mb_substr($ref,0,500)]);
    }catch(\Throwable $e){}
    echo json_encode(['success'=>true]);exit;
}

// Frontend error logging
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='error'){
    $input=json_decode(file_get_contents('php://input'),true);
    if(!$input){echo json_encode(['success'=>false]);exit;}
    try{
        $pdo=$d->getConnection();
        $pdo->prepare("INSERT INTO error_logs (type,message,source,line_number,stack_trace,page,user_id,ip,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())")->execute([
            mb_substr($input['type']??'error',0,20),
            mb_substr($input['message']??'',0,500),
            mb_substr($input['source']??'',0,255),
            intval($input['line']??0),
            mb_substr($input['stack']??'',0,1000),
            mb_substr($input['page']??'',0,100),
            intval($input['userId']??0)?:null,
            $_SERVER['REMOTE_ADDR']??'',
            mb_substr($input['ua']??'',0,255),
        ]);
    }catch(\Throwable $e){}
    echo json_encode(['success'=>true]);exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid']);
