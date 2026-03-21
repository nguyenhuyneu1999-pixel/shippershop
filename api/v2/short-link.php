<?php
// ShipperShop API v2 — Link Shortener
// Create short links for posts, profiles, groups
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function sl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Create short link
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $type=$input['type']??'post'; // post, profile, group
    $id=intval($input['id']??0);
    if(!$id) sl_ok('Missing id');

    $urls=['post'=>'/post-detail.html?id=','profile'=>'/user.html?id=','group'=>'/group.html?id='];
    $path=$urls[$type]??$urls['post'];
    $fullUrl='https://shippershop.vn'.$path.$id;

    // Generate short code
    $code=base_convert(crc32($type.$id.time()),10,36);
    $shortUrl='https://shippershop.vn/r/'.$code;

    // Store mapping
    $key='short_'.$code;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if(!$existing) $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(['url'=>$fullUrl,'type'=>$type,'id'=>$id,'created'=>date('c'),'clicks'=>0])]);

    sl_ok('OK',['short_url'=>$shortUrl,'full_url'=>$fullUrl,'code'=>$code]);
}

// Resolve short link
if($_SERVER['REQUEST_METHOD']==='GET'){
    $code=trim($_GET['code']??'');
    if(!$code) sl_ok('OK',['url'=>'https://shippershop.vn']);
    $key='short_'.$code;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    if(!$row) sl_ok('Not found',['url'=>'https://shippershop.vn']);
    $data=json_decode($row['value'],true);
    // Increment clicks
    $data['clicks']=intval($data['clicks']??0)+1;
    $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($data),$key]);
    sl_ok('OK',['url'=>$data['url'],'clicks'=>$data['clicks']]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
