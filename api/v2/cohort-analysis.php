<?php
// ShipperShop API v2 — Admin Cohort Analysis
// Track user retention by signup month cohort
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

$d=db();

function ca2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ca2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ca2_fail('Admin only',403);

$data=cache_remember('cohort_analysis', function() use($d) {
    // Get monthly signup cohorts (last 6 months)
    $cohorts=[];
    for($i=5;$i>=0;$i--){
        $monthStart=date('Y-m-01',strtotime("-$i months"));
        $monthEnd=date('Y-m-t',strtotime($monthStart));
        $monthLabel=date('m/Y',strtotime($monthStart));

        $signups=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at BETWEEN ? AND ?",[$monthStart,$monthEnd.' 23:59:59'])['c']);
        if($signups===0) continue;

        // Retention: how many posted in subsequent months
        $retention=[];
        for($w=0;$w<=3;$w++){
            $checkStart=date('Y-m-01',strtotime($monthStart." +$w months"));
            $checkEnd=date('Y-m-t',strtotime($checkStart));
            $active=intval($d->fetchOne("SELECT COUNT(DISTINCT p.user_id) as c FROM posts p JOIN users u ON p.user_id=u.id WHERE u.created_at BETWEEN ? AND ? AND p.created_at BETWEEN ? AND ? AND p.`status`='active'",[$monthStart,$monthEnd.' 23:59:59',$checkStart,$checkEnd.' 23:59:59'])['c']);
            $retention[]=['month'=>'M+'.$w,'active'=>$active,'rate'=>$signups>0?round($active/$signups*100,1):0];
        }

        $cohorts[]=['cohort'=>$monthLabel,'signups'=>$signups,'retention'=>$retention];
    }

    return ['cohorts'=>$cohorts,'months_analyzed'=>count($cohorts)];
}, 1800);

ca2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
