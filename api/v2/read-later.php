<?php
// ShipperShop API v2 — Read Later
// Save posts to read later queue (separate from bookmarks)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function rl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='read_later_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $ids=$row?json_decode($row['value'],true):[];
    if($ids){
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id IN ($ph) AND p.`status`='active' ORDER BY p.created_at DESC",$ids);
        rl_ok('OK',['posts'=>$posts,'count'=>count($posts)]);
    }
    rl_ok('OK',['posts'=>[],'count'=>0]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    if(!$postId) rl_ok('Missing post_id');

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $ids=$row?json_decode($row['value'],true):[];

    $idx=array_search($postId,$ids);
    if($idx!==false){
        array_splice($ids,$idx,1);
        $msg='Da bo khoi danh sach doc sau';$saved=false;
    }else{
        array_unshift($ids,$postId);
        if(count($ids)>100) $ids=array_slice($ids,0,100);
        $msg='Da luu de doc sau!';$saved=true;
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($ids)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($ids))]);

    rl_ok($msg,['saved'=>$saved,'count'=>count($ids)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
