<?php
// ShipperShop API v2 — Admin User Engagement Score V2
// Comprehensive engagement scoring: posts, likes, comments, followers, streaks
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

function esv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function esv2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') esv2_fail('Admin only',403);

$action=$_GET['action']??'';

if(!$action||$action==='leaderboard'){
    $data=cache_remember('eng_score_v2_lb', function() use($d) {
        $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.total_posts,u.shipping_company,
            (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as followers,
            (SELECT COALESCE(SUM(likes_count),0) FROM posts WHERE user_id=u.id AND `status`='active') as total_likes,
            (SELECT current_streak FROM user_streaks WHERE user_id=u.id) as streak,
            (SELECT COALESCE(SUM(xp),0) FROM user_xp WHERE user_id=u.id) as xp
            FROM users u WHERE u.`status`='active' AND u.total_posts>=1 ORDER BY u.total_posts DESC LIMIT 30");

        foreach($users as &$u){
            $score=intval($u['total_posts'])*3+intval($u['total_likes'])*1+intval($u['followers'])*5+intval($u['streak'])*2+intval($u['xp'])*0.1;
            $u['engagement_score']=round($score);
            $u['tier']=$score>=500?'diamond':($score>=200?'gold':($score>=100?'silver':($score>=30?'bronze':'starter')));
        }unset($u);

        usort($users,function($a,$b){return $b['engagement_score']-$a['engagement_score'];});
        return $users;
    }, 600);
    esv2_ok('OK',$data);
}

// Single user score
if($action==='user'){
    $targetId=intval($_GET['user_id']??0);
    if(!$targetId) esv2_ok('OK',null);
    $u=$d->fetchOne("SELECT fullname,avatar,total_posts FROM users WHERE id=? AND `status`='active'",[$targetId]);
    if(!$u) esv2_ok('Not found');
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$targetId])['c']);
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$targetId])['s']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$targetId])['current_streak']??0);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$targetId])['s']);
    $score=intval($u['total_posts'])*3+$totalLikes+$followers*5+$streak*2+round($xp*0.1);
    $breakdown=['posts'=>intval($u['total_posts'])*3,'likes'=>$totalLikes,'followers'=>$followers*5,'streak'=>$streak*2,'xp'=>round($xp*0.1)];
    esv2_ok('OK',['user'=>$u,'score'=>$score,'breakdown'=>$breakdown,'followers'=>$followers,'streak'=>$streak,'xp'=>$xp]);
}

esv2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
