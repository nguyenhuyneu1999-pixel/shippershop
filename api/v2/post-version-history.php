<?php
// ShipperShop API v2 — Post Version History
// Tinh nang: Theo doi lich su chinh sua bai viet
// Track post edit history with diff comparison
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

function pvh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$postId=intval($_GET['post_id']??0);

if($_SERVER['REQUEST_METHOD']==='GET'){
    if(!$postId) pvh_ok('Missing post_id');
    $key='post_versions_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $versions=$row?json_decode($row['value'],true):[];
    // Add current version
    $post=$d->fetchOne("SELECT content,image,updated_at FROM posts WHERE id=? AND `status`='active'",[$postId]);
    if($post){
        $current=['version'=>count($versions)+1,'content'=>$post['content'],'image'=>$post['image'],'timestamp'=>$post['updated_at']??date('c'),'is_current'=>true];
        array_unshift($versions,$current);
    }
    pvh_ok('OK',['versions'=>$versions,'count'=>count($versions),'post_id'=>$postId]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    if(!$postId) pvh_ok('Missing post_id');
    // Save current content as version before edit
    $post=$d->fetchOne("SELECT content,image,user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
    if(!$post||intval($post['user_id'])!==$uid) pvh_ok('Not found or not owner');
    $key='post_versions_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $versions=$row?json_decode($row['value'],true):[];
    $versions[]=['version'=>count($versions)+1,'content'=>$post['content'],'image'=>$post['image'],'timestamp'=>date('c'),'editor'=>$uid];
    if(count($versions)>20) $versions=array_slice($versions,-20);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($versions),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($versions)]);
    pvh_ok('Da luu phien ban '.(count($versions)));
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
