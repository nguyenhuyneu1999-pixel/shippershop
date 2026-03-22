<?php
// ShipperShop API v2 — User Activity Summary
// Activity heatmap data, streak info, contribution graph
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

$d=db();$action=$_GET['action']??'';

function ua_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) ua_ok('OK',[]);

// Activity heatmap (last 365 days — GitHub-style contribution graph)
if(!$action||$action==='heatmap'){
    $days=min(intval($_GET['days']??90),365);
    $data=cache_remember('user_heatmap_'.$userId.'_'.$days, function() use($d,$userId,$days) {
        // Posts per day
        $posts=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at)",[$userId]);
        // Comments per day
        $comments=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at)",[$userId]);
        // Likes per day
        $likes=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM likes WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at)",[$userId]);

        // Merge into single heatmap
        $map=[];
        foreach($posts as $r){$d2=$r['day'];if(!isset($map[$d2]))$map[$d2]=0;$map[$d2]+=intval($r['count'])*3;} // Posts worth 3x
        foreach($comments as $r){$d2=$r['day'];if(!isset($map[$d2]))$map[$d2]=0;$map[$d2]+=intval($r['count'])*2;}
        foreach($likes as $r){$d2=$r['day'];if(!isset($map[$d2]))$map[$d2]=0;$map[$d2]+=intval($r['count']);}

        $result=[];
        foreach($map as $day=>$score){$result[]=['date'=>$day,'score'=>$score];}
        usort($result,function($a,$b){return strcmp($a['date'],$b['date']);});
        return $result;
    }, 300);
    ua_ok('OK',['heatmap'=>$data,'user_id'=>$userId,'days'=>$days]);
}

// Activity summary (totals + recent)
if($action==='summary'){
    $u=$d->fetchOne("SELECT total_posts,total_success,total_comments,created_at FROM users WHERE id=?",[$userId]);
    if(!$u) ua_ok('OK',[]);

    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$userId])['c']);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$userId])['c']);
    $streak=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$userId]);
    $xpTotal=intval($d->fetchOne("SELECT SUM(xp) as t FROM user_xp WHERE user_id=?",[$userId])['t']);

    // Days since joined
    $joined=strtotime($u['created_at']);
    $daysSince=max(1,floor((time()-$joined)/86400));
    $avgPostsDay=round(intval($u['total_posts'])/$daysSince,1);

    ua_ok('OK',[
        'posts'=>intval($u['total_posts']),
        'comments'=>intval($u['total_comments']),
        'deliveries'=>intval($u['total_success']),
        'followers'=>$followers,
        'following'=>$following,
        'groups'=>$groups,
        'xp'=>$xpTotal,
        'streak'=>$streak?intval($streak['current_streak']):0,
        'longest_streak'=>$streak?intval($streak['longest_streak']):0,
        'joined'=>$u['created_at'],
        'days_active'=>$daysSince,
        'avg_posts_day'=>$avgPostsDay,
    ]);
}

ua_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
