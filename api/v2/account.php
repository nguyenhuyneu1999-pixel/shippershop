<?php
// ShipperShop API v2 — Account Management
// Deactivate, reactivate, delete account, change email
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ac_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ac_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$input=$_SERVER['REQUEST_METHOD']==='POST'?json_decode(file_get_contents('php://input'),true):[];

// Deactivate (hide profile, stop notifications, can reactivate)
if($action==='deactivate'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $password=trim($input['password']??'');
    $user=$d->fetchOne("SELECT password FROM users WHERE id=?",[$uid]);
    if(!$user||!password_verify($password,$user['password'])) ac_fail('Sai mật khẩu');

    $d->query("UPDATE users SET `status`='deactivated',is_online=0 WHERE id=?",[$uid]);
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'deactivate','Account deactivated',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ac_ok('Tài khoản đã tạm khóa. Đăng nhập lại để kích hoạt.');
}

// Reactivate (happens on login if status=deactivated)
if($action==='reactivate'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $d->query("UPDATE users SET `status`='active' WHERE id=? AND `status`='deactivated'",[$uid]);
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'reactivate','Account reactivated',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ac_ok('Tài khoản đã được kích hoạt lại!');
}

// Delete account permanently (30-day grace period)
if($action==='delete'&&$_SERVER['REQUEST_METHOD']==='POST'){
    rate_enforce('account_delete',1,86400);
    $password=trim($input['password']??'');
    $reason=trim($input['reason']??'');
    $user=$d->fetchOne("SELECT password FROM users WHERE id=?",[$uid]);
    if(!$user||!password_verify($password,$user['password'])) ac_fail('Sai mật khẩu');

    // Schedule deletion (30 days)
    $deleteAt=date('Y-m-d H:i:s',time()+30*86400);
    $d->query("UPDATE users SET `status`='pending_deletion',verification_note=? WHERE id=?",['DELETE_AT:'.$deleteAt.' REASON:'.$reason,$uid]);
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'delete_request',?,?,NOW())")->execute([$uid,'Scheduled deletion: '.$deleteAt.' Reason: '.$reason,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ac_ok('Tài khoản sẽ bị xóa vĩnh viễn sau 30 ngày. Đăng nhập trước đó để hủy.',['delete_at'=>$deleteAt]);
}

// Cancel deletion
if($action==='cancel_delete'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $d->query("UPDATE users SET `status`='active',verification_note=NULL WHERE id=? AND `status`='pending_deletion'",[$uid]);
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'cancel_delete','Deletion cancelled',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ac_ok('Đã hủy yêu cầu xóa tài khoản');
}

// Change email
if($action==='change_email'&&$_SERVER['REQUEST_METHOD']==='POST'){
    rate_enforce('change_email',3,3600);
    $newEmail=trim($input['email']??'');
    $password=trim($input['password']??'');
    if(!$newEmail||!filter_var($newEmail,FILTER_VALIDATE_EMAIL)) ac_fail('Email không hợp lệ');
    $user=$d->fetchOne("SELECT password FROM users WHERE id=?",[$uid]);
    if(!$user||!password_verify($password,$user['password'])) ac_fail('Sai mật khẩu');
    $exists=$d->fetchOne("SELECT id FROM users WHERE email=? AND id!=?",[$newEmail,$uid]);
    if($exists) ac_fail('Email đã được sử dụng');
    $d->query("UPDATE users SET email=? WHERE id=?",[$newEmail,$uid]);
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'change_email',?,?,NOW())")->execute([$uid,'Email changed to: '.$newEmail,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ac_ok('Đã đổi email');
}

// Account status
if(!$action||$action==='status'){
    $user=$d->fetchOne("SELECT `status`,created_at,last_active,is_verified,two_factor_enabled,verification_note FROM users WHERE id=?",[$uid]);
    $deleteAt=null;
    if($user['status']==='pending_deletion'&&$user['verification_note']){
        preg_match('/DELETE_AT:(\S+)/',$user['verification_note'],$m);
        $deleteAt=$m[1]??null;
    }
    ac_ok('OK',['status'=>$user['status'],'created_at'=>$user['created_at'],'last_active'=>$user['last_active'],'is_verified'=>intval($user['is_verified']),'two_factor'=>intval($user['two_factor_enabled']),'pending_delete'=>$deleteAt]);
}

ac_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
