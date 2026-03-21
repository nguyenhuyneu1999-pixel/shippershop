<?php
// ShipperShop API v2 — QR Code Generator
// Generate QR for user profiles, group invites, posts
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$action=$_GET['action']??'';

function qr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Generate QR URL (uses free API)
$type=$_GET['type']??'profile';
$id=intval($_GET['id']??0);

$urls=[
    'profile'=>'https://shippershop.vn/user.html?id='.$id,
    'group'=>'https://shippershop.vn/group.html?id='.$id,
    'post'=>'https://shippershop.vn/post-detail.html?id='.$id,
    'invite'=>'https://shippershop.vn/register.html?ref='.$id,
];

$url=$urls[$type]??$urls['profile'];
$size=intval($_GET['size']??200);
$size=min(max($size,100),500);

// Use Google Chart API for QR (free, no dependencies)
$qrUrl='https://chart.googleapis.com/chart?chs='.$size.'x'.$size.'&cht=qr&chl='.urlencode($url).'&choe=UTF-8&chld=M|2';

// Alternative: generate SVG QR inline (no external dependency)
// For now, return the URL + an SVG placeholder
qr_ok('OK',[
    'url'=>$url,
    'qr_image'=>$qrUrl,
    'size'=>$size,
    'type'=>$type,
    'id'=>$id,
]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
