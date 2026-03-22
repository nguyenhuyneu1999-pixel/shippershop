<?php
// ShipperShop API v2 — User Compare
// Compare stats between two users side-by-side
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function uc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid1=intval($_GET['user1']??0);
$uid2=intval($_GET['user2']??0);
if(!$uid1||!$uid2) uc_ok('Need user1 and user2');

$getStats=function($uid) use($d){
    $u=$d->fetchOne("SELECT id,fullname,avatar,shipping_company,total_posts,total_success,created_at FROM users WHERE id=? AND `status`='active'",[$uid]);
    if(!$u) return null;
    $days=max(1,floor((time()-strtotime($u['created_at']))/86400));
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$uid])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$uid])['c']);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$uid])['c']);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$uid])['s']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$uid])['current_streak']??0);
    return [
        'user'=>['id'=>intval($u['id']),'fullname'=>$u['fullname'],'avatar'=>$u['avatar'],'company'=>$u['shipping_company']],
        'stats'=>['posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'comments'=>$comments,'followers'=>$followers,'following'=>$following,'groups'=>$groups,'xp'=>$xp,'streak'=>$streak,'days_active'=>$days,'avg_posts_per_day'=>round(intval($u['total_posts'])/$days,2)],
    ];
};

$s1=$getStats($uid1);
$s2=$getStats($uid2);
if(!$s1||!$s2) uc_ok('User not found');

// Determine winners per metric
$metrics=['posts','deliveries','comments','followers','following','groups','xp','streak','avg_posts_per_day'];
$wins=['user1'=>0,'user2'=>0,'tie'=>0];
$comparison=[];
foreach($metrics as $m){
    $v1=$s1['stats'][$m]??0;$v2=$s2['stats'][$m]??0;
    $winner=$v1>$v2?'user1':($v2>$v1?'user2':'tie');
    $wins[$winner]++;
    $comparison[$m]=['user1'=>$v1,'user2'=>$v2,'winner'=>$winner];
}

uc_ok('OK',['user1'=>$s1,'user2'=>$s2,'comparison'=>$comparison,'wins'=>$wins]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
