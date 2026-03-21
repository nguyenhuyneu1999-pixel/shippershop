<?php
// ShipperShop API v2 — User Verification (blue checkmark for verified shippers)
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

function vr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function vr_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Check verification status
    if($action==='status'){
        $tid=intval($_GET['user_id']??($uid??0));
        if(!$tid) vr_fail('Missing user');
        $u=$d->fetchOne("SELECT is_verified,verified_at,verification_note FROM users WHERE id=?",[$tid]);
        if(!$u) vr_fail('User not found',404);
        vr_ok('OK',['is_verified'=>intval($u['is_verified']),'verified_at'=>$u['verified_at'],'note'=>$u['verification_note']]);
    }

    // List verification requests (admin)
    if($action==='requests'){
        $admin=require_auth();
        $u=$d->fetchOne("SELECT role FROM users WHERE id=?",[$admin]);
        if(!$u||$u['role']!=='admin') vr_fail('Admin only',403);
        // Users who requested verification (verification_note starts with 'REQUEST:')
        $requests=$d->fetchAll("SELECT id,fullname,email,avatar,shipping_company,verification_note,created_at FROM users WHERE verification_note LIKE 'REQUEST:%' AND is_verified=0 ORDER BY created_at DESC LIMIT 50");
        vr_ok('OK',$requests);
    }

    // Verified users list
    if($action==='verified'){
        $limit=min(intval($_GET['limit']??30),100);
        $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company,verified_at FROM users WHERE is_verified=1 AND `status`='active' ORDER BY verified_at DESC LIMIT $limit");
        vr_ok('OK',$users);
    }

    vr_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Request verification (user)
    if($action==='request'){
        rate_enforce('verify_request',3,86400);
        $note=trim($input['note']??'');
        $company=trim($input['shipping_company']??'');
        if(!$note||mb_strlen($note)<10) vr_fail('Mô tả tối thiểu 10 ký tự (VD: mã shipper, hãng vận chuyển)');
        $d->query("UPDATE users SET verification_note=? WHERE id=?",['REQUEST: '.$company.' — '.$note,$uid]);
        // Notify admin
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (2,'system','Yêu cầu xác minh',?,?,NOW())")->execute(['User #'.$uid.' yêu cầu xác minh',json_encode(['user_id'=>$uid])]);}catch(\Throwable $e){}
        vr_ok('Đã gửi yêu cầu xác minh! Admin sẽ xem xét.');
    }

    // Approve verification (admin)
    if($action==='approve'){
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') vr_fail('Admin only',403);
        $tid=intval($input['user_id']??0);
        if(!$tid) vr_fail('Missing user_id');
        $d->query("UPDATE users SET is_verified=1,verified_at=NOW(),verification_note=? WHERE id=?",[trim($input['note']??'Verified by admin'),$tid]);
        // Notify user
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'system','Đã xác minh!','Tài khoản của bạn đã được xác minh ✓','{}',NOW())")->execute([$tid]);}catch(\Throwable $e){}
        // Award XP
        try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'verified',50,'Tài khoản được xác minh',NOW())")->execute([$tid]);}catch(\Throwable $e){}
        vr_ok('Đã xác minh user #'.$tid);
    }

    // Reject verification (admin)
    if($action==='reject'){
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') vr_fail('Admin only',403);
        $tid=intval($input['user_id']??0);
        $reason=trim($input['reason']??'Không đủ thông tin');
        $d->query("UPDATE users SET verification_note=? WHERE id=?",['REJECTED: '.$reason,$tid]);
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'system','Xác minh bị từ chối',?,'{}',NOW())")->execute([$tid,$reason]);}catch(\Throwable $e){}
        vr_ok('Đã từ chối');
    }

    // Revoke verification (admin)
    if($action==='revoke'){
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') vr_fail('Admin only',403);
        $tid=intval($input['user_id']??0);
        $d->query("UPDATE users SET is_verified=0,verified_at=NULL,verification_note=NULL WHERE id=?",[$tid]);
        vr_ok('Đã thu hồi xác minh');
    }

    vr_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
