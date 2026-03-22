<?php
// ShipperShop API v2 — Admin Content Moderation Queue
// Review flagged/reported content, approve/reject
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

function mq_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mq_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') mq_fail('Admin only',403);

// List reported posts
if(!$action||$action==='list'){
    $reports=$d->fetchAll("SELECT pr.*,p.content as post_content,p.user_id as author_id,u.fullname as reporter_name,u2.fullname as author_name FROM post_reports pr JOIN posts p ON pr.post_id=p.id JOIN users u ON pr.user_id=u.id JOIN users u2 ON p.user_id=u2.id WHERE pr.`status`='pending' ORDER BY pr.created_at DESC LIMIT 50");
    $counts=['pending'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']),'reviewed'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`!='pending'")['c'])];
    mq_ok('OK',['reports'=>$reports,'counts'=>$counts]);
}

// Approve (dismiss report)
if($action==='approve'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $reportId=intval($input['report_id']??0);
    if(!$reportId) mq_fail('Missing report_id');
    $d->query("UPDATE post_reports SET `status`='dismissed',resolved_by=?,resolved_at=NOW() WHERE id=?",[$uid,$reportId]);
    mq_ok('Da bo qua bao cao');
}

// Remove post
if($action==='remove'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $reportId=intval($input['report_id']??0);
    $postId=intval($input['post_id']??0);
    if($reportId) $d->query("UPDATE post_reports SET `status`='actioned',resolved_by=?,resolved_at=NOW() WHERE id=?",[$uid,$reportId]);
    if($postId) $d->query("UPDATE posts SET `status`='removed' WHERE id=?",[$postId]);
    mq_ok('Da go bai viet');
}

// Stats
if($action==='stats'){
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM post_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day");
    $byReason=$d->fetchAll("SELECT reason,COUNT(*) as count FROM post_reports GROUP BY reason ORDER BY count DESC LIMIT 10");
    mq_ok('OK',['daily'=>$daily,'by_reason'=>$byReason]);
}

mq_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
