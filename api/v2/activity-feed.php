<?php
// ShipperShop API v2 — Social Activity Feed
// Shows what people you follow are doing (posts, reactions, follows, stories)
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

$d=db();$action=$_GET['action']??'';

function af_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??20),50);
$offset=($page-1)*$limit;

// Friends' activity
if(!$action||$action==='friends'){
    $activities=$d->fetchAll("SELECT af.*,u.fullname as user_name,u.avatar as user_avatar
        FROM activity_feed af
        JOIN users u ON af.user_id=u.id
        WHERE af.user_id IN (SELECT following_id FROM follows WHERE follower_id=?)
        AND af.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY af.created_at DESC
        LIMIT $limit OFFSET $offset",[$uid]);

    // Enrich with target info
    foreach($activities as &$a){
        if($a['target_type']==='post'&&$a['target_id']){
            $post=$d->fetchOne("SELECT content,images FROM posts WHERE id=?",[$a['target_id']]);
            if($post){
                $a['target_preview']=mb_substr($post['content']??'',0,80);
                $imgs=json_decode($post['images']??'[]',true);
                $a['target_image']=is_array($imgs)&&$imgs?$imgs[0]:null;
            }
        }elseif($a['target_type']==='user'&&$a['target_id']){
            $targetUser=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[$a['target_id']]);
            if($targetUser){$a['target_name']=$targetUser['fullname'];$a['target_avatar']=$targetUser['avatar'];}
        }
    }unset($a);

    af_ok('OK',['activities'=>$activities,'page'=>$page]);
}

// My activity
if($action==='me'){
    $activities=$d->fetchAll("SELECT * FROM activity_feed WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
    af_ok('OK',['activities'=>$activities,'page'=>$page]);
}

// Post author stats
if($action==='author_stats'){
    $authorId=intval($_GET['user_id']??0);
    if(!$authorId) af_ok('OK',[]);

    $stats=cache_remember('author_stats_'.$authorId, function() use($d,$authorId) {
        $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$authorId])['c']);
        $totalLikes=intval($d->fetchOne("SELECT SUM(likes_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$authorId])['s']);
        $totalComments=intval($d->fetchOne("SELECT SUM(comments_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$authorId])['s']);
        $avgLikes=$totalPosts>0?round($totalLikes/$totalPosts,1):0;
        $topPost=$d->fetchOne("SELECT id,content,likes_count,comments_count FROM posts WHERE user_id=? AND `status`='active' ORDER BY likes_count DESC LIMIT 1",[$authorId]);
        $postingDays=intval($d->fetchOne("SELECT COUNT(DISTINCT DATE(created_at)) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",[$authorId])['c']);

        return [
            'total_posts'=>$totalPosts,
            'total_likes'=>$totalLikes,
            'total_comments'=>$totalComments,
            'avg_likes_per_post'=>$avgLikes,
            'posting_frequency'=>$postingDays.' ngày/30 ngày qua',
            'top_post'=>$topPost,
        ];
    }, 300);

    af_ok('OK',$stats);
}

af_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
