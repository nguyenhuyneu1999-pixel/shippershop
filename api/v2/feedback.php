<?php
// ShipperShop API v2 — User Feedback
// Submit app feedback, bug reports, feature requests
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

function fb_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fb_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Submit feedback
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='submit')){
    $uid=optional_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $type=$input['type']??'feedback'; // feedback, bug, feature, other
    $message=trim($input['message']??'');
    $rating=intval($input['rating']??0); // 1-5
    if(!$message||mb_strlen($message)<5) fb_fail('Noi dung toi thieu 5 ky tu');

    // Store in audit_log
    $pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'feedback',?,?,NOW())")->execute([$uid??0,json_encode(['type'=>$type,'message'=>$message,'rating'=>$rating,'ua'=>mb_substr($_SERVER['HTTP_USER_AGENT']??'',0,200)],JSON_UNESCAPED_UNICODE),$_SERVER['REMOTE_ADDR']??'']);

    fb_ok('Cam on ban da gop y! Chung toi se xem xet.');
}

// Admin: list feedback
if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') fb_fail('Admin only',403);

    $limit=min(intval($_GET['limit']??50),200);
    $feedbacks=$d->fetchAll("SELECT al.*,u.fullname,u.avatar FROM audit_log al LEFT JOIN users u ON al.user_id=u.id WHERE al.action='feedback' ORDER BY al.created_at DESC LIMIT $limit");
    foreach($feedbacks as &$f){$f['parsed']=json_decode($f['detail'],true);}unset($f);
    fb_ok('OK',['feedbacks'=>$feedbacks,'total'=>count($feedbacks)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
