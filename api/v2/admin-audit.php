<?php
// ShipperShop API v2 — Admin Audit Trail
// Complete audit log viewer with filters and search
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function aa_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function aa_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') aa_fail('Admin only',403);

$action=$_GET['action']??'';
$limit=min(intval($_GET['limit']??50),200);
$page=max(1,intval($_GET['page']??1));
$offset=($page-1)*$limit;

// List with filters
if(!$action||$action==='list'){
    $filterAction=$_GET['filter_action']??'';
    $filterUser=intval($_GET['filter_user']??0);
    $w=["1=1"];$p=[];
    if($filterAction){$w[]="al.action=?";$p[]=$filterAction;}
    if($filterUser){$w[]="al.user_id=?";$p[]=$filterUser;}
    $wc=implode(' AND ',$w);

    $logs=$d->fetchAll("SELECT al.*,u.fullname,u.avatar FROM audit_log al LEFT JOIN users u ON al.user_id=u.id WHERE $wc ORDER BY al.created_at DESC LIMIT $limit OFFSET $offset",$p);
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE $wc",$p)['c']);
    foreach($logs as &$l){$l['parsed']=json_decode($l['details']??'',true);}unset($l);
    aa_ok('OK',['logs'=>$logs,'total'=>$total,'page'=>$page,'has_more'=>count($logs)>=$limit]);
}

// Distinct actions for filter dropdown
if($action==='actions'){
    $actions=$d->fetchAll("SELECT DISTINCT action,COUNT(*) as count FROM audit_log GROUP BY action ORDER BY count DESC");
    aa_ok('OK',$actions);
}

// Stats
if($action==='stats'){
    $today=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE created_at >= CURDATE()")['c']);
    $week=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $byAction=$d->fetchAll("SELECT action,COUNT(*) as count FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY action ORDER BY count DESC LIMIT 10");
    aa_ok('OK',['today'=>$today,'week'=>$week,'by_action'=>$byAction]);
}

aa_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
