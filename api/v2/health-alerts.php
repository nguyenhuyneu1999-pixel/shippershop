<?php
// ShipperShop API v2 — Health Alerts
// Monitor thresholds, trigger alerts when exceeded
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$action=$_GET['action']??'';

function ha_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ha_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: check system status
if(!$action||$action==='check'){
    $alerts=[];

    // Error rate
    $errors=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page LIKE '_js_error%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
    if($errors>50) $alerts[]=['type'=>'danger','metric'=>'error_rate','value'=>$errors,'threshold'=>50,'message'=>'Lỗi JS cao: '.$errors.' trong 1 giờ'];
    elseif($errors>10) $alerts[]=['type'=>'warning','metric'=>'error_rate','value'=>$errors,'threshold'=>10,'message'=>'Lỗi JS tăng: '.$errors.' trong 1 giờ'];

    // Disk usage
    $diskFree=@disk_free_space('/');$diskTotal=@disk_total_space('/');
    if($diskTotal&&$diskFree){
        $usedPct=round(($diskTotal-$diskFree)/$diskTotal*100,1);
        if($usedPct>90) $alerts[]=['type'=>'danger','metric'=>'disk','value'=>$usedPct,'threshold'=>90,'message'=>'Disk gần đầy: '.$usedPct.'%'];
        elseif($usedPct>80) $alerts[]=['type'=>'warning','metric'=>'disk','value'=>$usedPct,'threshold'=>80,'message'=>'Disk cao: '.$usedPct.'%'];
    }

    // DB connection
    try{$d->fetchOne("SELECT 1 as ok");}catch(\Throwable $e){$alerts[]=['type'=>'danger','metric'=>'database','message'=>'DB connection failed'];}

    // Pending deposits
    $pendingDeposits=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
    if($pendingDeposits>10) $alerts[]=['type'=>'warning','metric'=>'deposits','value'=>$pendingDeposits,'message'=>$pendingDeposits.' khoản nạp chờ duyệt'];

    // Pending reports
    $pendingReports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
    if($pendingReports>20) $alerts[]=['type'=>'warning','metric'=>'reports','value'=>$pendingReports,'message'=>$pendingReports.' báo cáo chờ xử lý'];

    $status=count($alerts)?'warning':'healthy';
    foreach($alerts as $a){if($a['type']==='danger'){$status='critical';break;}}

    ha_ok('OK',['status'=>$status,'alerts'=>$alerts,'checked_at'=>date('c')]);
}

// Admin: alert history
if($action==='history'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ha_fail('Admin only',403);
    $logs=$d->fetchAll("SELECT * FROM audit_log WHERE action LIKE 'alert:%' ORDER BY created_at DESC LIMIT 50");
    ha_ok('OK',$logs);
}

ha_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
