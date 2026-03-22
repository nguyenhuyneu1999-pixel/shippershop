<?php
// ShipperShop API v2 — Performance Monitoring
// Collect client-side perf metrics, slow query log, error rates
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

function pf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Collect client perf metrics (beacon from browser)
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='report')){
    $input=json_decode(file_get_contents('php://input'),true);
    // Store as analytics
    $page=$input['page']??'';
    $metrics=$input['metrics']??[];
    if($page&&$metrics){
        $pdo=db()->getConnection();
        $pdo->prepare("INSERT INTO analytics_views (page,referrer,created_at) VALUES (?,?,NOW())")->execute(['_perf_'.$page,json_encode($metrics)]);
    }
    pf_ok('OK');
}

// Admin: get perf dashboard
if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') pf_ok('OK',[]);

    $hours=min(intval($_GET['hours']??24),168);

    // Error rate
    $errors=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page LIKE '_js_error%' AND created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);
    $totalViews=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);
    $errorRate=$totalViews>0?round($errors/$totalViews*100,2):0;

    // Top error pages
    $topErrors=$d->fetchAll("SELECT REPLACE(page,'_js_error_','') as page,COUNT(*) as count FROM analytics_views WHERE page LIKE '_js_error%' AND created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY page ORDER BY count DESC LIMIT 10");

    // API response times (from perf reports)
    $perfData=$d->fetchAll("SELECT page,referrer FROM analytics_views WHERE page LIKE '_perf_%' AND created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY created_at DESC LIMIT 100");

    // Request volume per hour
    $hourly=$d->fetchAll("SELECT HOUR(created_at) as hour,COUNT(*) as requests FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY HOUR(created_at) ORDER BY hour");

    pf_ok('OK',[
        'error_rate'=>$errorRate,
        'total_errors'=>$errors,
        'total_views'=>$totalViews,
        'top_errors'=>$topErrors,
        'hourly_traffic'=>$hourly,
        'period_hours'=>$hours,
    ]);
}

pf_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
