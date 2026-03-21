<?php
// ShipperShop API v2 — Media Gallery
// User media (images/videos from posts), upload management
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

function md_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function md_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// User's media gallery (images from posts)
if(!$action||$action==='gallery'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) md_fail('Missing user_id');
    $page=max(1,intval($_GET['page']??1));$limit=min(intval($_GET['limit']??24),60);$offset=($page-1)*$limit;

    // Get posts with images
    $posts=$d->fetchAll("SELECT id,images,video_url,created_at FROM posts WHERE user_id=? AND `status`='active' AND (images IS NOT NULL OR video_url IS NOT NULL) ORDER BY created_at DESC LIMIT $limit OFFSET $offset",[$userId]);

    $media=[];
    foreach($posts as $p){
        if($p['images']){
            $imgs=json_decode($p['images'],true);
            if(is_array($imgs)){
                foreach($imgs as $img){
                    $media[]=['type'=>'image','url'=>$img,'post_id'=>intval($p['id']),'created_at'=>$p['created_at']];
                }
            }
        }
        if($p['video_url']){
            $media[]=['type'=>'video','url'=>$p['video_url'],'post_id'=>intval($p['id']),'created_at'=>$p['created_at']];
        }
    }

    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND (images IS NOT NULL OR video_url IS NOT NULL)",[$userId])['c']);
    md_ok('OK',['media'=>$media,'total_posts_with_media'=>$total,'page'=>$page]);
}

// Media stats
if($action==='stats'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) md_fail('Missing user_id');
    $imgCount=0;$vidCount=0;
    $posts=$d->fetchAll("SELECT images,video_url FROM posts WHERE user_id=? AND `status`='active' AND (images IS NOT NULL OR video_url IS NOT NULL)",[$userId]);
    foreach($posts as $p){
        if($p['images']){$imgs=json_decode($p['images'],true);if(is_array($imgs))$imgCount+=count($imgs);}
        if($p['video_url'])$vidCount++;
    }
    md_ok('OK',['images'=>$imgCount,'videos'=>$vidCount,'total'=>$imgCount+$vidCount]);
}

md_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
