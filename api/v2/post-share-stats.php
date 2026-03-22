<?php
// ShipperShop API v2 — Post Share Statistics
// Track where/how posts are shared, share analytics
// session removed: JWT auth only
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

function ps_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Track a share event
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=optional_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    $platform=$input['platform']??'copy'; // copy, facebook, zalo, messenger, twitter, other
    if(!$postId) ps_ok('Missing post_id');

    // Store share event
    $key='share_stats_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $stats=$row?json_decode($row['value'],true):['total'=>0,'platforms'=>[],'history'=>[]];
    $stats['total']=intval($stats['total'])+1;
    if(!isset($stats['platforms'][$platform])) $stats['platforms'][$platform]=0;
    $stats['platforms'][$platform]++;
    $stats['history'][]=[ 'user_id'=>$uid,'platform'=>$platform,'at'=>date('c')];
    if(count($stats['history'])>100) $stats['history']=array_slice($stats['history'],-100);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($stats),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($stats)]);

    // Also update post shares_count
    $d->query("UPDATE posts SET shares_count=shares_count+1 WHERE id=?",[$postId]);

    ps_ok('Tracked',['total'=>$stats['total']]);
}

// Get share stats for a post
if($_SERVER['REQUEST_METHOD']==='GET'){
    $postId=intval($_GET['post_id']??0);
    if(!$postId) ps_ok('OK',['total'=>0,'platforms'=>[]]);
    $key='share_stats_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $stats=$row?json_decode($row['value'],true):['total'=>0,'platforms'=>[]];
    unset($stats['history']); // Don't expose full history
    ps_ok('OK',$stats);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
