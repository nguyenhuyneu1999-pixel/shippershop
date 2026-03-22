<?php
// ShipperShop API v2 — Post Highlights Reel
// User's curated best posts showcase
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

function ph_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// GET: user's highlights
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) ph_ok('OK',['highlights'=>[]]);
    $key='highlights_'.$userId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $postIds=$row?json_decode($row['value'],true):[];
    $highlights=[];
    if($postIds){
        $placeholders=implode(',',array_fill(0,count($postIds),'?'));
        $highlights=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id IN ($placeholders) AND p.`status`='active' ORDER BY p.likes_count DESC",$postIds);
    }
    ph_ok('OK',['highlights'=>$highlights,'count'=>count($highlights),'max'=>10]);
}

// POST: add/remove highlight
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $postId=intval($input['post_id']??0);
    if(!$postId) ph_ok('Missing post_id');

    $key='highlights_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $postIds=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        if(!in_array($postId,$postIds)) $postIds[]=$postId;
        if(count($postIds)>10) ph_ok('Toi da 10 bai highlight');
    }
    if($action==='remove') $postIds=array_values(array_filter($postIds,function($id) use($postId){return $id!==$postId;}));

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($postIds)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($postIds))]);
    ph_ok($action==='remove'?'Da go':'Da them highlight!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
