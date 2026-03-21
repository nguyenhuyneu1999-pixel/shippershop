<?php
// ShipperShop API v2 — Report Analytics (Admin)
// Breakdown of reports by reason, resolution rate, top reported users
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ra_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ra_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') ra_fail('Admin only',403);

$days=min(intval($_GET['days']??30),365);

// Overview
if(!$action||$action==='overview'){
    $data=cache_remember('report_analytics_'.$days, function() use($d,$days) {
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY)")['c']);
        $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE resolved_by IS NULL AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY)")['c']);
        $resolved=$total-$pending;
        $byReason=$d->fetchAll("SELECT reason,COUNT(*) as count FROM post_reports WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY reason ORDER BY count DESC");
        $byResolution=$d->fetchAll("SELECT COALESCE(resolution,'pending') as resolution,COUNT(*) as count FROM post_reports WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY resolution ORDER BY count DESC");
        $topReported=$d->fetchAll("SELECT p.user_id,u.fullname,u.avatar,COUNT(*) as report_count FROM post_reports pr JOIN posts p ON pr.post_id=p.id JOIN users u ON p.user_id=u.id WHERE pr.created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY p.user_id ORDER BY report_count DESC LIMIT 10");
        $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM post_reports WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        $avgResolveTime=$d->fetchOne("SELECT AVG(TIMESTAMPDIFF(HOUR,created_at,resolved_at)) as avg_hours FROM post_reports WHERE resolved_at IS NOT NULL AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY)");

        return [
            'total'=>$total,'pending'=>$pending,'resolved'=>$resolved,
            'resolution_rate'=>$total>0?round($resolved/$total*100,1):0,
            'avg_resolve_hours'=>round(floatval($avgResolveTime['avg_hours']??0),1),
            'by_reason'=>$byReason,'by_resolution'=>$byResolution,
            'top_reported'=>$topReported,'daily'=>$daily,
            'period_days'=>$days,
        ];
    }, 600);
    ra_ok('OK',$data);
}

ra_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
