<?php
// ShipperShop API v2 — Ban Appeals
// Users submit ban appeals, admin review/approve/reject
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

function ba_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ba_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Submit appeal (banned user can still call this)
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='submit')){
    $input=json_decode(file_get_contents('php://input'),true);
    $email=trim($input['email']??'');
    $reason=trim($input['reason']??'');
    if(!$email||!$reason) ba_fail('Nhap email va ly do');
    $user=$d->fetchOne("SELECT id,`status` FROM users WHERE email=?",[$email]);
    if(!$user) ba_fail('Email khong ton tai',404);
    // Store appeal in settings
    $key='ban_appeals';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $appeals=$row?json_decode($row['value'],true):[];
    $appeals[]=['id'=>count($appeals)+1,'user_id'=>intval($user['id']),'email'=>$email,'reason'=>$reason,'status'=>'pending','created_at'=>date('c')];
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($appeals),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($appeals)]);
    ba_ok('Da gui yeu cau. Vui long cho admin xem xet.');
}

// Admin: list appeals
if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ba_fail('Admin only',403);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='ban_appeals'");
    $appeals=$row?json_decode($row['value'],true):[];
    $status=$_GET['status']??'';
    if($status) $appeals=array_values(array_filter($appeals,function($a) use($status){return ($a['status']??'')===$status;}));
    ba_ok('OK',$appeals);
}

// Admin: resolve appeal
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='resolve'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ba_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);
    $appealId=intval($input['appeal_id']??0);
    $resolution=$input['resolution']??''; // approved, rejected
    if(!$appealId||!in_array($resolution,['approved','rejected'])) ba_fail('Invalid data');

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='ban_appeals'");
    $appeals=$row?json_decode($row['value'],true):[];
    foreach($appeals as &$a){
        if(($a['id']??0)===$appealId){
            $a['status']=$resolution;
            $a['resolved_by']=$uid;
            $a['resolved_at']=date('c');
            if($resolution==='approved'&&isset($a['user_id'])){
                $d->query("UPDATE users SET `status`='active' WHERE id=?",[$a['user_id']]);
            }
        }
    }unset($a);
    $d->query("UPDATE settings SET value=? WHERE `key`='ban_appeals'",[json_encode($appeals)]);
    ba_ok($resolution==='approved'?'Da mo khoa tai khoan':'Da tu choi');
}

ba_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
