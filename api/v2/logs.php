<?php
// ShipperShop API v2 — Logs Viewer (admin only)
// View error logs, cron logs, audit logs
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function lg_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function lg_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') lg_fail('Admin only',403);

$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??30),100);
$offset=($page-1)*$limit;

// Audit logs
if(!$action||$action==='audit'){
    $userId=intval($_GET['user_id']??0);
    $w="1=1";$p=[];
    if($userId){$w.=" AND user_id=?";$p[]=$userId;}
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE $w",$p)['c']);
    $logs=$d->fetchAll("SELECT a.*,u.fullname as user_name FROM audit_log a LEFT JOIN users u ON a.user_id=u.id WHERE $w ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset",$p);
    echo json_encode(['success'=>true,'data'=>['logs'=>$logs,'meta'=>['page'=>$page,'total'=>$total,'per_page'=>$limit]]]);exit;
}

// Cron logs
if($action==='cron'){
    $logs=$d->fetchAll("SELECT * FROM cron_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM cron_logs")['c']);
    echo json_encode(['success'=>true,'data'=>['logs'=>$logs,'meta'=>['page'=>$page,'total'=>$total]]]);exit;
}

// Error logs (from error_logs table if exists)
if($action==='errors'){
    try{
        $logs=$d->fetchAll("SELECT * FROM error_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM error_logs")['c']);
        echo json_encode(['success'=>true,'data'=>['logs'=>$logs,'meta'=>['page'=>$page,'total'=>$total]]]);exit;
    }catch(\Throwable $e){
        lg_ok('No error_logs table',[]);
    }
}

// Rate limit stats
if($action==='rate_limits'){
    $stats=$d->fetchAll("SELECT endpoint,ip,COUNT(*) as hits,MAX(created_at) as last_hit FROM rate_limits WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY endpoint,ip ORDER BY hits DESC LIMIT 50");
    lg_ok('OK',$stats);
}

// Login attempts
if($action==='login_attempts'){
    $logs=$d->fetchAll("SELECT * FROM login_attempts ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    lg_ok('OK',$logs);
}

lg_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
