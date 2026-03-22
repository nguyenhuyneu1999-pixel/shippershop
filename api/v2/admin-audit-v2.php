<?php
// ShipperShop API v2 — Admin Audit V2
// Advanced audit log viewer: filter by user, action, date, export
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

function aav2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function aav2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') aav2_fail('Admin only',403);

$page=max(1,intval($_GET['page']??1));$perPage=20;$offset=($page-1)*$perPage;
$filterUser=intval($_GET['user_id']??0);
$filterAction=trim($_GET['action_filter']??'');
$filterDate=$_GET['date']??'';

$where="1=1";$params=[];
if($filterUser){$where.=" AND a.user_id=?";$params[]=$filterUser;}
if($filterAction){$where.=" AND a.action LIKE ?";$params[]='%'.$filterAction.'%';}
if($filterDate){$where.=" AND DATE(a.created_at)=?";$params[]=$filterDate;}

$total=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log a WHERE $where",$params)['c']);
$logs=$d->fetchAll("SELECT a.*,u.fullname FROM audit_log a LEFT JOIN users u ON a.user_id=u.id WHERE $where ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset",$params);

// Action stats
$actionStats=$d->fetchAll("SELECT action, COUNT(*) as c FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY action ORDER BY c DESC LIMIT 10");

aav2_ok('OK',['logs'=>$logs,'total'=>$total,'page'=>$page,'per_page'=>$perPage,'pages'=>ceil($total/$perPage),'action_stats'=>$actionStats]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
