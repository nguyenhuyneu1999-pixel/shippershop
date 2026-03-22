<?php
// ShipperShop API v2 — Post View Tracking
// Track post views, impressions, unique viewers
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function pv_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Track view (POST)
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='track')){
    $uid=optional_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    if(!$postId) pv_ok('skip');
    $ip=$_SERVER['REMOTE_ADDR']??'';
    // Deduplicate: same user/IP + post within 1 hour
    $key=$uid?'u'.$uid:'ip'.md5($ip);
    $exists=$d->fetchOne("SELECT id FROM analytics_views WHERE page=? AND referrer=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1",['post_'.$postId,$key]);
    if(!$exists){
        $pdo->prepare("INSERT INTO analytics_views (page,referrer,ip,user_id,created_at) VALUES (?,?,?,?,NOW())")->execute(['post_'.$postId,$key,$ip,$uid]);
    }
    pv_ok('tracked');
}

// Batch track (multiple posts seen in feed)
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='batch'){
    $uid=optional_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $postIds=$input['post_ids']??[];
    if(!is_array($postIds)||count($postIds)>50) pv_ok('skip');
    $ip=$_SERVER['REMOTE_ADDR']??'';
    $key=$uid?'u'.$uid:'ip'.md5($ip);
    $tracked=0;
    foreach($postIds as $pid){
        $pid=intval($pid);if(!$pid)continue;
        $exists=$d->fetchOne("SELECT id FROM analytics_views WHERE page=? AND referrer=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1",['post_'.$pid,$key]);
        if(!$exists){
            try{$pdo->prepare("INSERT INTO analytics_views (page,referrer,ip,user_id,created_at) VALUES (?,?,?,?,NOW())")->execute(['post_'.$pid,$key,$ip,$uid]);$tracked++;}catch(\Throwable $e){}
        }
    }
    pv_ok('OK',['tracked'=>$tracked]);
}

// Get view stats for a post (GET)
if($action==='stats'){
    $postId=intval($_GET['post_id']??0);
    if(!$postId) pv_ok('OK',['views'=>0]);
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page=?",['post_'.$postId])['c']);
    $unique=intval($d->fetchOne("SELECT COUNT(DISTINCT referrer) as c FROM analytics_views WHERE page=?",['post_'.$postId])['c']);
    $today=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page=? AND created_at > CURDATE()",['post_'.$postId])['c']);
    pv_ok('OK',['total'=>$total,'unique'=>$unique,'today'=>$today]);
}

// My post analytics (author view)
if($action==='my'){
    $uid=require_auth();
    $days=min(intval($_GET['days']??7),90);
    // Total views on my posts
    $myPosts=$d->fetchAll("SELECT id FROM posts WHERE user_id=? AND `status`='active'",[$uid]);
    $pids=array_column($myPosts,'id');
    if(!$pids) pv_ok('OK',['total_views'=>0,'posts'=>[]]);

    $ph=implode(',',array_fill(0,count($pids),'?'));
    $pages=array_map(function($id){return 'post_'.$id;},$pids);
    $pagePh=implode(',',array_fill(0,count($pages),'?'));

    $totalViews=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page IN ($pagePh)",$pages)['c']);

    // Per-post breakdown (top 10)
    $breakdown=$d->fetchAll("SELECT REPLACE(page,'post_','') as post_id,COUNT(*) as views FROM analytics_views WHERE page IN ($pagePh) GROUP BY page ORDER BY views DESC LIMIT 10",$pages);

    // Daily trend
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as views FROM analytics_views WHERE page IN ($pagePh) AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day",$pages);

    pv_ok('OK',['total_views'=>$totalViews,'top_posts'=>$breakdown,'daily'=>$daily]);
}

pv_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
