<?php
// ShipperShop API v2 — Admin Notification Blast
// Send push notification to all users or segments
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

function nb_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function nb_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') nb_fail('Admin only',403);

// Send blast
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='send')){
    $input=json_decode(file_get_contents('php://input'),true);
    $title=trim($input['title']??'');
    $message=trim($input['message']??'');
    $segment=$input['segment']??'all'; // all, active, pro, vip
    if(!$title||!$message) nb_fail('Nhap tieu de va noi dung');

    // Get target users
    $where="u.`status`='active'";
    if($segment==='active') $where.=" AND u.id IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))";
    elseif($segment==='pro') $where.=" AND u.id IN (SELECT user_id FROM user_subscriptions WHERE plan_id>=2 AND expires_at > NOW())";
    elseif($segment==='vip') $where.=" AND u.id IN (SELECT user_id FROM user_subscriptions WHERE plan_id>=3 AND expires_at > NOW())";

    $users=$d->fetchAll("SELECT u.id FROM users u WHERE $where");
    $sent=0;
    foreach($users as $u){
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'admin_blast',?,?,?,NOW())")->execute([intval($u['id']),$title,$message,json_encode(['segment'=>$segment,'by'=>$uid])]);$sent++;}catch(\Throwable $e){}
    }

    // Log
    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'notif_blast',?,?,NOW())")->execute([$uid,json_encode(['title'=>$title,'segment'=>$segment,'sent'=>$sent]),$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    nb_ok('Da gui '.$sent.' thong bao!',['sent'=>$sent,'segment'=>$segment]);
}

// History
if($_SERVER['REQUEST_METHOD']==='GET'){
    $history=$d->fetchAll("SELECT * FROM audit_log WHERE action='notif_blast' ORDER BY created_at DESC LIMIT 20");
    foreach($history as &$h){$h['parsed']=json_decode($h['details'],true);}unset($h);
    nb_ok('OK',$history);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
