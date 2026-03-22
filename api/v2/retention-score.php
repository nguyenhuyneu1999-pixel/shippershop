<?php
// ShipperShop API v2 — Admin User Retention Score
// Individual user retention risk scoring
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

function rs2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rs2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rs2_fail('Admin only',403);

$targetId=intval($_GET['user_id']??0);

// Single user score
if($targetId){
    $u=$d->fetchOne("SELECT fullname,avatar,total_posts,created_at FROM users WHERE id=? AND `status`='active'",[$targetId]);
    if(!$u) rs2_ok('User not found');

    $lastPost=$d->fetchOne("SELECT MAX(created_at) as lp FROM posts WHERE user_id=? AND `status`='active'",[$targetId]);
    $daysSincePost=$lastPost['lp']?floor((time()-strtotime($lastPost['lp']))/86400):999;
    $posts7d=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$targetId])['c']);
    $posts30d=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$targetId])['c']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$targetId])['current_streak']??0);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$targetId])['c']);

    // Score 0-100 (higher = more likely to stay)
    $score=50;
    if($daysSincePost<=1) $score+=20; elseif($daysSincePost<=7) $score+=10; elseif($daysSincePost>=30) $score-=30;
    if($posts7d>=3) $score+=15; elseif($posts7d>=1) $score+=5;
    if($streak>=7) $score+=10; elseif($streak>=3) $score+=5;
    if($followers>=10) $score+=5;
    $score=max(0,min(100,$score));
    $risk=$score>=70?'low':($score>=40?'medium':'high');

    rs2_ok('OK',['user'=>$u,'score'=>$score,'risk'=>$risk,'factors'=>['days_since_post'=>$daysSincePost,'posts_7d'=>$posts7d,'posts_30d'=>$posts30d,'streak'=>$streak,'followers'=>$followers]]);
}

// Bulk: at-risk users
$atRisk=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.total_posts,MAX(p.created_at) as last_post FROM users u LEFT JOIN posts p ON u.id=p.user_id AND p.`status`='active' WHERE u.`status`='active' AND u.total_posts>=1 GROUP BY u.id HAVING last_post < DATE_SUB(NOW(), INTERVAL 7 DAY) OR last_post IS NULL ORDER BY last_post ASC LIMIT 30");

rs2_ok('OK',['at_risk'=>$atRisk,'count'=>count($atRisk)]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
