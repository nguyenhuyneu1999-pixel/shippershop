<?php
// ShipperShop API v2 — Admin Activity Heatmap
// User activity heatmap: hour x day grid showing post/engagement intensity
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

function ah2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ah2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ah2_fail('Admin only',403);

$days=min(intval($_GET['days']??30),90);

$data=cache_remember('activity_heatmap_'.$days, function() use($d,$days) {
    $raw=$d->fetchAll("SELECT HOUR(created_at) as h, DAYOFWEEK(created_at) as dow, COUNT(*) as posts, SUM(likes_count+comments_count) as engagement FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY HOUR(created_at), DAYOFWEEK(created_at)");

    // Build 7x24 grid
    $grid=[];$maxVal=0;
    $dayNames=['','CN','T2','T3','T4','T5','T6','T7'];
    for($dow=1;$dow<=7;$dow++){
        $row=['day'=>$dayNames[$dow],'hours'=>[]];
        for($h=0;$h<24;$h++){
            $val=0;
            foreach($raw as $r){if(intval($r['dow'])===$dow&&intval($r['h'])===$h) $val=intval($r['posts']);}
            $row['hours'][]=$val;
            if($val>$maxVal) $maxVal=$val;
        }
        $grid[]=$row;
    }

    // Peak times
    $peaks=[];
    foreach($raw as $r){$peaks[]=['hour'=>intval($r['h']),'day'=>$dayNames[intval($r['dow'])],'posts'=>intval($r['posts']),'engagement'=>intval($r['engagement'])];}
    usort($peaks,function($a,$b){return $b['posts']-$a['posts'];});

    return ['grid'=>$grid,'max_value'=>$maxVal,'peak_times'=>array_slice($peaks,0,5),'period_days'=>$days];
}, 1800);

ah2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
