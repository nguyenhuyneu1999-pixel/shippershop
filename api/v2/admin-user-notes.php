<?php
// ShipperShop API v2 — Admin User Notes
// Private notes on users (only visible to admins)
// session removed: JWT auth only
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
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') an_fail('Admin only',403);

if($_SERVER['REQUEST_METHOD']==='GET'){
    $targetId=intval($_GET['user_id']??0);
    if(!$targetId) an_fail('Missing user_id');
    // Notes stored in settings table with key pattern
    $key='admin_note_user_'.$targetId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    an_ok('OK',['notes'=>$notes,'user_id'=>$targetId]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Add note
    if(!$action||$action==='add'){
        $targetId=intval($input['user_id']??0);
        $text=trim($input['note']??'');
        $category=$input['category']??'general'; // general, warning, ban, payment, support
        if(!$targetId||!$text) an_fail('Missing user_id or note');

        $key='admin_note_user_'.$targetId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $notes=$row?json_decode($row['value'],true):[];
        $notes[]=['id'=>count($notes)+1,'text'=>$text,'category'=>$category,'admin_id'=>$uid,'admin_name'=>getUserName($uid),'created_at'=>date('c')];

        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($notes),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($notes)]);

        an_ok('Đã thêm ghi chú',['count'=>count($notes)]);
    }

    // Delete note
    if($action==='delete'){
        $targetId=intval($input['user_id']??0);
        $noteId=intval($input['note_id']??0);
        $key='admin_note_user_'.$targetId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $notes=$row?json_decode($row['value'],true):[];
        $notes=array_values(array_filter($notes,function($n) use($noteId){return ($n['id']??0)!==$noteId;}));
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($notes),$key]);
        an_ok('Đã xóa ghi chú');
    }

    an_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}

function getUserName($uid){$u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);return $u?$u['fullname']:'Admin';}
