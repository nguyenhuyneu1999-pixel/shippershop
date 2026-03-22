<?php
// ShipperShop API v2 — Admin Churn Prediction
// Identify users at risk of churning based on activity patterns
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

function cp2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cp2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') cp2_fail('Admin only',403);

$data=cache_remember('churn_prediction', function() use($d) {
    // At risk: posted before but not in last 14 days
    $atRisk=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.total_posts,MAX(p.created_at) as last_post FROM users u JOIN posts p ON u.id=p.user_id WHERE u.`status`='active' AND u.total_posts>=3 GROUP BY u.id HAVING last_post < DATE_SUB(NOW(), INTERVAL 14 DAY) AND last_post >= DATE_SUB(NOW(), INTERVAL 60 DAY) ORDER BY last_post DESC LIMIT 30");

    // Already churned: no activity 60+ days
    $churned=intval($d->fetchOne("SELECT COUNT(*) as c FROM users u WHERE u.`status`='active' AND u.total_posts>=1 AND u.id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY))")['c']);

    // Declining: posted less this week than avg
    $declining=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.total_posts,COALESCE((SELECT COUNT(*) FROM posts WHERE user_id=u.id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)),0) as week_posts,COALESCE((SELECT COUNT(*)/4 FROM posts WHERE user_id=u.id AND created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)),0) as avg_week FROM users u WHERE u.`status`='active' AND u.total_posts>=5 HAVING week_posts < avg_week * 0.5 AND avg_week >= 1 ORDER BY avg_week DESC LIMIT 20");

    return ['at_risk'=>$atRisk,'at_risk_count'=>count($atRisk),'churned_count'=>$churned,'declining'=>$declining,'declining_count'=>count($declining)];
}, 600);

cp2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
