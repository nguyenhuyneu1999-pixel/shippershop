<?php
// ShipperShop API v2 — User Weekly Report
// Personalized weekly summary: stats comparison, highlights, goals
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

function wr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

$data=cache_remember('weekly_report_'.$uid, function() use($d,$uid) {
    // This week
    $tw=['posts'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c']),
         'likes'=>intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['s']),
         'comments'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c']),
         'new_followers'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c'])];

    // Last week (for comparison)
    $lw=['posts'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c']),
         'likes'=>intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['s']),
         'comments'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c'])];

    // Deltas
    $delta=function($a,$b){if($b==0)return $a>0?100:0;return round(($a-$b)/$b*100);};
    $changes=['posts'=>$delta($tw['posts'],$lw['posts']),'likes'=>$delta($tw['likes'],$lw['likes']),'comments'=>$delta($tw['comments'],$lw['comments'])];

    // Top post this week
    $topPost=$d->fetchOne("SELECT id,content,likes_count,comments_count FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY (likes_count+comments_count) DESC LIMIT 1",[$uid]);

    // Streak
    $streak=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$uid]);

    return ['this_week'=>$tw,'last_week'=>$lw,'changes'=>$changes,'top_post'=>$topPost,'streak'=>['current'=>intval($streak['current_streak']??0),'longest'=>intval($streak['longest_streak']??0)],'week_start'=>date('Y-m-d',strtotime('monday this week')),'week_end'=>date('Y-m-d')];
}, 3600);

wr_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
