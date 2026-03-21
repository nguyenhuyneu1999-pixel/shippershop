<?php
// ShipperShop API v2 — Content Recommendations
// "For You" personalized feed, similar posts, suggested follows
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
$limit=min(intval($_GET['limit']??10),30);

function rc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=optional_auth();

// For You — personalized based on interests
if(!$action||$action==='for_you'){
    if(!$uid){
        // Guest: return hot posts
        $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.is_draft=0 ORDER BY (p.likes_count*3+p.comments_count*5) DESC LIMIT $limit");
        rc_ok('OK',['posts'=>$posts,'source'=>'popular']);
    }

    $posts=cache_remember('recommend_'.$uid, function() use($d,$uid,$limit) {
        // Get user's liked post authors + shipping companies
        $likedAuthors=$d->fetchAll("SELECT DISTINCT p.user_id FROM likes l JOIN posts p ON l.post_id=p.id WHERE l.user_id=? ORDER BY l.created_at DESC LIMIT 20",[$uid]);
        $authorIds=array_column($likedAuthors,'user_id');

        // Get followed users' shipping companies
        $companies=$d->fetchAll("SELECT DISTINCT u.shipping_company FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=? AND u.shipping_company IS NOT NULL AND u.shipping_company!=''",[$uid]);
        $companyList=array_column($companies,'shipping_company');

        $results=[];

        // Posts from authors user engaged with but doesn't follow
        if($authorIds){
            $ph=implode(',',array_fill(0,count($authorIds),'?'));
            $fromEngaged=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified,'engaged_author' as rec_source FROM posts p JOIN users u ON p.user_id=u.id WHERE p.user_id IN ($ph) AND p.user_id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) AND p.user_id!=? AND p.`status`='active' AND p.is_draft=0 ORDER BY p.created_at DESC LIMIT 5",array_merge($authorIds,[$uid,$uid]));
            $results=array_merge($results,$fromEngaged);
        }

        // Posts from same shipping companies
        if($companyList){
            $ph2=implode(',',array_fill(0,count($companyList),'?'));
            $fromCompany=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified,'same_company' as rec_source FROM posts p JOIN users u ON p.user_id=u.id WHERE u.shipping_company IN ($ph2) AND p.user_id!=? AND p.`status`='active' AND p.is_draft=0 AND p.id NOT IN (SELECT post_id FROM likes WHERE user_id=?) ORDER BY p.likes_count DESC LIMIT 5",array_merge($companyList,[$uid,$uid]));
            $results=array_merge($results,$fromCompany);
        }

        // Fill with trending if not enough
        if(count($results)<$limit){
            $existing=array_column($results,'id');
            $exFilter=$existing?"AND p.id NOT IN (".implode(',',$existing).")":'';
            $trending=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified,'trending' as rec_source FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.is_draft=0 AND p.user_id!=? $exFilter ORDER BY (p.likes_count*3+p.comments_count*5)/POW(TIMESTAMPDIFF(HOUR,p.created_at,NOW())+2,1.5) DESC LIMIT ".($limit-count($results)),[$uid]);
            $results=array_merge($results,$trending);
        }

        // Shuffle for variety
        shuffle($results);
        return array_slice($results,0,$limit);
    }, 180);

    rc_ok('OK',['posts'=>$posts,'source'=>'personalized']);
}

// Similar posts (based on a given post)
if($action==='similar'){
    $pid=intval($_GET['post_id']??0);
    if(!$pid) rc_ok('OK',[]);

    $post=$d->fetchOne("SELECT user_id,type,province,district,shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.id=?",[$pid]);
    if(!$post) rc_ok('OK',[]);

    // Find posts with same type/location/company
    $similar=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id!=? AND p.`status`='active' AND p.is_draft=0 AND (p.type=? OR p.province=? OR u.shipping_company=?) ORDER BY p.likes_count DESC LIMIT ?",[$pid,$post['type']??'',$post['province']??'',$post['shipping_company']??'',$limit]);

    rc_ok('OK',$similar);
}

// Suggested follows (users you might want to follow)
if($action==='suggested_follows'){
    if(!$uid) rc_ok('OK',[]);

    $suggestions=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_verified,
        (SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active') as post_count,
        (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as follower_count,
        (SELECT COUNT(*) FROM follows f1 JOIN follows f2 ON f1.following_id=f2.following_id WHERE f1.follower_id=? AND f2.follower_id=u.id) as mutual_follows
        FROM users u
        WHERE u.id!=? AND u.`status`='active'
        AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?)
        AND u.id NOT IN (SELECT blocked_id FROM user_blocks WHERE blocker_id=?)
        ORDER BY mutual_follows DESC, follower_count DESC
        LIMIT ?",[$uid,$uid,$uid,$uid,$limit]);

    rc_ok('OK',$suggestions);
}

rc_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
