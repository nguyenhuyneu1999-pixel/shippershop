<?php
// ShipperShop API v2 — Post Expiry
// Set auto-delete timer on posts (temporary posts)
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

function pe_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pe_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// GET: check expiry status
if($_SERVER['REQUEST_METHOD']==='GET'){
    $postId=intval($_GET['post_id']??0);
    if(!$postId) pe_ok('OK',['has_expiry'=>false]);
    $key='post_expiry_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    if(!$row) pe_ok('OK',['has_expiry'=>false]);
    $data=json_decode($row['value'],true);
    $data['expired']=$data['expires_at']&&strtotime($data['expires_at'])<time();
    $data['remaining_seconds']=max(0,strtotime($data['expires_at'])-time());
    pe_ok('OK',$data);
}

// POST: set expiry
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    $hours=intval($input['hours']??24);
    if(!$postId) pe_fail('Missing post_id');
    if($hours<1||$hours>720) pe_fail('1-720 gio');

    // Verify ownership
    $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
    if(!$post||intval($post['user_id'])!==$uid) pe_fail('Khong co quyen',403);

    $expiresAt=date('Y-m-d H:i:s',time()+$hours*3600);
    $data=['has_expiry'=>true,'post_id'=>$postId,'hours'=>$hours,'expires_at'=>$expiresAt,'set_by'=>$uid,'set_at'=>date('c')];

    $key='post_expiry_'.$postId;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($data),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($data)]);

    pe_ok('Bai se tu dong xoa sau '.$hours.' gio',$data);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
