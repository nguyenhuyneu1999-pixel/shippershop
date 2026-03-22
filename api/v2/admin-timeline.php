<?php
// ShipperShop API v2 — Admin Activity Timeline
// Recent platform activity: new users, posts, reports, payments
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function at_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function at_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') at_fail('Admin only',403);

$hours=min(intval($_GET['hours']??24),168);
$events=[];

// New users
$users=$d->fetchAll("SELECT id,fullname,avatar,created_at FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY created_at DESC LIMIT 20");
foreach($users as $u){$events[]=['type'=>'new_user','icon'=>'👤','label'=>'Thanh vien moi: '.$u['fullname'],'user_id'=>intval($u['id']),'time'=>$u['created_at']];}

// New posts
$posts=$d->fetchAll("SELECT p.id,p.content,u.fullname,p.created_at FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY p.created_at DESC LIMIT 20");
foreach($posts as $p){$events[]=['type'=>'new_post','icon'=>'📝','label'=>$p['fullname'].' dang bai moi','post_id'=>intval($p['id']),'preview'=>mb_substr($p['content'],0,60),'time'=>$p['created_at']];}

// Reports
$reports=$d->fetchAll("SELECT pr.id,pr.reason,pr.created_at,u.fullname FROM post_reports pr LEFT JOIN users u ON pr.user_id=u.id WHERE pr.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY pr.created_at DESC LIMIT 10");
foreach($reports as $r){$events[]=['type'=>'report','icon'=>'🚨','label'=>($r['fullname']??'User').' bao cao: '.$r['reason'],'time'=>$r['created_at']];}

// Payments
$payments=$d->fetchAll("SELECT pp.order_code,pp.amount,pp.`status`,pp.created_at,u.fullname FROM payos_payments pp JOIN users u ON pp.user_id=u.id WHERE pp.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY pp.created_at DESC LIMIT 10");
foreach($payments as $p){$events[]=['type'=>'payment','icon'=>'💰','label'=>$p['fullname'].' nap '.number_format(intval($p['amount'])).'d ('.$p['status'].')','time'=>$p['created_at']];}

// Audit log
$audits=$d->fetchAll("SELECT al.action,al.detail,al.created_at,u.fullname FROM audit_log al LEFT JOIN users u ON al.user_id=u.id WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY al.created_at DESC LIMIT 10");
foreach($audits as $a){$events[]=['type'=>'audit','icon'=>'📋','label'=>($a['fullname']??'System').': '.$a['action'],'detail'=>mb_substr($a['detail']??'',0,80),'time'=>$a['created_at']];}

// Sort by time desc
usort($events,function($a,$b){return strcmp($b['time'],$a['time']);});
$events=array_slice($events,0,50);

at_ok('OK',['events'=>$events,'period_hours'=>$hours,'total'=>count($events)]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
