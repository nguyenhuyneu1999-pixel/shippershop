<?php
// ShipperShop API v2 — Admin Content Pipeline
// Track content from creation → review → publish → archive
// session removed: JWT auth only
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

function cp3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cp3_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') cp3_fail('Admin only',403);

$data=cache_remember('content_pipeline', function() use($d) {
    $draft=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='draft'")['c']);
    $scheduled=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='scheduled'")['c']);
    $published=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published'")['c']);
    $failed=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='failed'")['c']);

    $activePosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $deletedPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='deleted'")['c']);
    $reportedPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);

    // Daily publishing rate
    $daily=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
    $avgDaily=count($daily)>0?round(array_sum(array_column($daily,'c'))/count($daily),1):0;

    $stages=[
        ['stage'=>'Draft','count'=>$draft,'icon'=>'📝','color'=>'#94a3b8'],
        ['stage'=>'Scheduled','count'=>$scheduled,'icon'=>'⏰','color'=>'#7c3aed'],
        ['stage'=>'Published','count'=>$published,'icon'=>'✅','color'=>'#22c55e'],
        ['stage'=>'Failed','count'=>$failed,'icon'=>'❌','color'=>'#ef4444'],
    ];

    return ['stages'=>$stages,'live'=>['active'=>$activePosts,'deleted'=>$deletedPosts,'reported'=>$reportedPosts],'avg_daily'=>$avgDaily,'daily_trend'=>$daily];
}, 300);

cp3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
