<?php
// ShipperShop API v2 — Trending / Hot Feed Algorithm
// Rank posts by engagement score: likes*3 + comments*5 + shares*2, decay by age
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

function tr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=optional_auth();
$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??15),50);
$offset=($page-1)*$limit;

// Hot posts (engagement + time decay)
if(!$action||$action==='hot'){
    $period=$_GET['period']??'day'; // day, week, month
    $hours=['day'=>24,'week'=>168,'month'=>720][$period]??24;

    $posts=cache_remember('trending_hot_'.$period.'_'.$page, function() use($d,$hours,$limit,$offset,$uid) {
        return $d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified,
            (p.likes_count*3 + p.comments_count*5 + p.shares_count*2) / POW(TIMESTAMPDIFF(HOUR,p.created_at,NOW())+2, 1.5) as hot_score
            FROM posts p
            LEFT JOIN users u ON p.user_id=u.id
            WHERE p.`status`='active' AND p.is_draft=0 AND p.scheduled_at IS NULL
            AND p.created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)
            ORDER BY hot_score DESC
            LIMIT $limit OFFSET $offset");
    }, 120);

    // Add user_liked if authenticated
    if($uid&&$posts){
        $pids=array_column($posts,'id');
        if($pids){
            $ph=implode(',',array_fill(0,count($pids),'?'));
            $liked=$d->fetchAll("SELECT post_id FROM likes WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
            $likedSet=array_flip(array_column($liked,'post_id'));
            $saved=$d->fetchAll("SELECT post_id FROM saved_posts WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
            $savedSet=array_flip(array_column($saved,'post_id'));
            foreach($posts as &$p){$p['user_liked']=isset($likedSet[$p['id']]);$p['user_saved']=isset($savedSet[$p['id']]);}unset($p);
        }
    }

    tr_ok('OK',['posts'=>$posts,'period'=>$period,'page'=>$page]);
}

// Rising posts (most engagement gain in last 2 hours)
if($action==='rising'){
    $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id AND l.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)) as recent_likes,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id=p.id AND c.`status`='active' AND c.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)) as recent_comments
        FROM posts p LEFT JOIN users u ON p.user_id=u.id
        WHERE p.`status`='active' AND p.created_at > DATE_SUB(NOW(), INTERVAL 48 HOUR) AND p.is_draft=0
        HAVING (recent_likes + recent_comments) > 0
        ORDER BY (recent_likes*3 + recent_comments*5) DESC
        LIMIT $limit OFFSET $offset");
    tr_ok('OK',['posts'=>$posts,'page'=>$page]);
}

// Top users (most engagement on their posts this period)
if($action==='top_users'){
    $period=$_GET['period']??'week';
    $hours=['day'=>24,'week'=>168,'month'=>720][$period]??168;
    $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_verified,
        COUNT(DISTINCT p.id) as post_count,
        SUM(p.likes_count) as total_likes,
        SUM(p.comments_count) as total_comments,
        SUM(p.likes_count*3 + p.comments_count*5) as score
        FROM posts p JOIN users u ON p.user_id=u.id
        WHERE p.`status`='active' AND p.created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR) AND u.`status`='active'
        GROUP BY u.id ORDER BY score DESC LIMIT 20");
    tr_ok('OK',$users);
}

// Trending topics (most discussed)
if($action==='topics'){
    $posts=$d->fetchAll("SELECT type,COUNT(*) as count FROM posts WHERE `status`='active' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) AND type IS NOT NULL AND type!='' GROUP BY type ORDER BY count DESC LIMIT 10");
    tr_ok('OK',$posts);
}

tr_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
