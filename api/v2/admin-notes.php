<?php
// ShipperShop API v2 — Admin User Notes
// Internal notes on users (only visible to admins)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function an_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function an_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') an_fail('Admin only',403);

if($_SERVER['REQUEST_METHOD']==='GET'){
    $targetId=intval($_GET['user_id']??0);
    if(!$targetId) an_fail('Missing user_id');
    $notes=$d->fetchAll("SELECT an.*,u.fullname as admin_name FROM admin_notes an LEFT JOIN users u ON an.admin_id=u.id WHERE an.target_user_id=? ORDER BY an.created_at DESC",[$targetId]);
    an_ok('OK',$notes);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    if(!$action||$action==='add'){
        $targetId=intval($input['user_id']??0);
        $note=trim($input['note']??'');
        if(!$targetId||!$note) an_fail('Missing data');
        $pdo->prepare("INSERT INTO admin_notes (target_user_id,admin_id,note,created_at) VALUES (?,?,?,NOW())")->execute([$targetId,$uid,$note]);
        an_ok('Đã thêm ghi chú');
    }

    if($action==='delete'){
        $noteId=intval($input['note_id']??0);
        if(!$noteId) an_fail('Missing note_id');
        $d->query("DELETE FROM admin_notes WHERE id=? AND admin_id=?",[$noteId,$uid]);
        an_ok('Đã xóa');
    }

    an_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
