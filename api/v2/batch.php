<?php
// ShipperShop API v2 — Batch API
// Execute multiple API calls in a single request to reduce roundtrips
// POST {requests: [{path:"/posts.php?limit=3"}, {path:"/notifications.php?action=unread_count"}]}
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

if($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'message'=>'POST only']);exit;}

$input=json_decode(file_get_contents('php://input'),true);
$requests=$input['requests']??[];

if(!$requests||!is_array($requests)||count($requests)>10){
    echo json_encode(['success'=>false,'message'=>'1-10 requests required']);exit;
}

rate_enforce('batch_api',20,60);

$results=[];
$baseUrl='https://shippershop.vn/api/v2';
$authHeader='';
if(!empty($_SERVER['HTTP_AUTHORIZATION'])) $authHeader=$_SERVER['HTTP_AUTHORIZATION'];

foreach($requests as $i=>$req){
    $path=trim($req['path']??'');
    if(!$path){$results[]=['success'=>false,'error'=>'Missing path'];continue;}

    // Security: only allow v2 API paths
    if(strpos($path,'/')!==0) $path='/'.$path;
    if(strpos($path,'..')!==false||strpos($path,'/../')!==false){
        $results[]=['success'=>false,'error'=>'Invalid path'];continue;
    }

    $url=$baseUrl.$path;
    $ctx=stream_context_create(['http'=>[
        'method'=>'GET',
        'header'=>"Authorization: ".$authHeader."\r\nAccept: application/json\r\n",
        'timeout'=>5,
        'ignore_errors'=>true,
    ]]);

    $start=microtime(true);
    $resp=@file_get_contents($url,false,$ctx);
    $ms=round((microtime(true)-$start)*1000);

    $status=200;
    if(isset($http_response_header)){
        foreach($http_response_header as $h){
            if(preg_match('/^HTTP\/\S+\s+(\d+)/',$h,$m)) $status=intval($m[1]);
        }
    }

    $data=json_decode($resp,true);
    $results[]=['path'=>$path,'status'=>$status,'ms'=>$ms,'data'=>$data];
}

echo json_encode(['success'=>true,'data'=>$results,'count'=>count($results)],JSON_UNESCAPED_UNICODE);
