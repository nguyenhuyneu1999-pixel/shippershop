<?php
// ShipperShop API v2 — Admin Batch Operations
// Bulk actions on users, posts, reports
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

function ab_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ab_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ab_fail('Admin only',403);

if($_SERVER['REQUEST_METHOD']!=='POST') ab_fail('POST only');
$input=json_decode(file_get_contents('php://input'),true);

// Batch delete posts
if($action==='delete_posts'){
    $ids=$input['post_ids']??[];
    if(!is_array($ids)||count($ids)>100) ab_fail('Max 100 posts');
    $count=0;
    foreach($ids as $id){
        $id=intval($id);if(!$id)continue;
        $d->query("UPDATE posts SET `status`='deleted' WHERE id=?",[$id]);
        $count++;
    }
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'batch_delete_posts',?,?,NOW())")->execute([$uid,'Deleted '.$count.' posts',$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ab_ok('Đã xóa '.$count.' bài viết',['deleted'=>$count]);
}

// Batch ban users
if($action==='ban_users'){
    $ids=$input['user_ids']??[];
    $reason=$input['reason']??'Vi phạm quy tắc';
    $days=max(1,intval($input['days']??7));
    if(!is_array($ids)||count($ids)>50) ab_fail('Max 50 users');
    $count=0;
    foreach($ids as $id){
        $id=intval($id);if(!$id||$id===$uid)continue;
        $d->query("UPDATE users SET `status`='banned' WHERE id=? AND role!='admin'",[$id]);
        $count++;
    }
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'batch_ban',?,?,NOW())")->execute([$uid,'Banned '.$count.' users: '.$reason,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ab_ok('Đã khóa '.$count.' tài khoản',['banned'=>$count]);
}

// Batch resolve reports
if($action==='resolve_reports'){
    $ids=$input['report_ids']??[];
    $resolution=$input['resolution']??'dismiss';
    if(!is_array($ids)||count($ids)>100) ab_fail('Max 100 reports');
    $count=0;
    foreach($ids as $id){
        $id=intval($id);if(!$id)continue;
        $d->query("UPDATE post_reports SET `status`=?,resolved_by=?,resolved_at=NOW() WHERE id=? AND `status`='pending'",[$resolution,$uid,$id]);
        $count++;
    }
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'batch_resolve',?,?,NOW())")->execute([$uid,'Resolved '.$count.' reports: '.$resolution,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ab_ok('Đã xử lý '.$count.' báo cáo',['resolved'=>$count]);
}

// Batch approve deposits
if($action==='approve_deposits'){
    $ids=$input['payment_ids']??[];
    if(!is_array($ids)||count($ids)>50) ab_fail('Max 50 deposits');
    $count=0;$totalAmount=0;
    foreach($ids as $oc){
        $oc=intval($oc);if(!$oc)continue;
        $payment=$d->fetchOne("SELECT * FROM payos_payments WHERE order_code=? AND `status` IN ('pending','manual')",[$oc]);
        if(!$payment)continue;
        $userId=intval($payment['user_id']);$amount=intval($payment['amount']);
        $d->query("UPDATE payos_payments SET `status`='completed',paid_at=NOW() WHERE order_code=?",[$oc]);
        $wallet=$d->fetchOne("SELECT id FROM wallets WHERE user_id=?",[$userId]);
        if($wallet){$d->query("UPDATE wallets SET balance=balance+? WHERE user_id=?",[$amount,$userId]);}
        else{$pdo->prepare("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,?,NOW())")->execute([$userId,$amount]);}
        $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,created_at) VALUES (?,'deposit',?,?,NOW())")->execute([$userId,$amount,'Batch approve #'.$oc]);
        $count++;$totalAmount+=$amount;
    }
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'batch_approve_deposits',?,?,NOW())")->execute([$uid,'Approved '.$count.' deposits, total: '.$totalAmount,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ab_ok('Đã duyệt '.$count.' khoản nạp ('.number_format($totalAmount).'đ)',['approved'=>$count,'total_amount'=>$totalAmount]);
}

// Batch send notification
if($action==='send_notification'){
    $targetIds=$input['user_ids']??[];
    $title=trim($input['title']??'');
    $message=trim($input['message']??'');
    $sendAll=!empty($input['send_all']);
    if(!$title||!$message) ab_fail('Nhập tiêu đề và nội dung');

    if($sendAll){
        $allUsers=$d->fetchAll("SELECT id FROM users WHERE `status`='active'");
        $targetIds=array_column($allUsers,'id');
    }
    if(count($targetIds)>5000) ab_fail('Max 5000 users');

    $count=0;
    foreach($targetIds as $tid){
        $tid=intval($tid);if(!$tid)continue;
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'system',?,?,'{}',NOW())")->execute([$tid,$title,$message]);$count++;}catch(\Throwable $e){}
    }
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'batch_notify',?,?,NOW())")->execute([$uid,'Sent to '.$count.' users: '.$title,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
    ab_ok('Đã gửi thông báo cho '.$count.' người',['sent'=>$count]);
}

ab_fail('Action không hợp lệ');

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
