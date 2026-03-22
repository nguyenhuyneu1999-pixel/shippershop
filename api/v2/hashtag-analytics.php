<?php
// ShipperShop API v2 — Hashtag Analytics
// Deep analytics per hashtag: usage over time, top users, engagement
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function ha_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Top hashtags
if(!$action||$action==='top'){
    $days=min(intval($_GET['days']??30),365);
    $data=cache_remember('hashtag_top_'.$days, function() use($d,$days) {
        $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) LIMIT 500");
        $tags=[];
        foreach($posts as $p){
            preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$p['content']??'',$m);
            foreach(($m[1]??[]) as $t){$t=mb_strtolower($t);$tags[$t]=($tags[$t]??0)+1;}
        }
        arsort($tags);
        $top=[];foreach(array_slice($tags,0,20,true) as $tag=>$count){$top[]=['tag'=>'#'.$tag,'count'=>$count];}
        return ['hashtags'=>$top,'total_unique'=>count($tags),'posts_scanned'=>count($posts),'period_days'=>$days];
    }, 600);
    ha_ok('OK',$data);
}

// Single hashtag detail
if($action==='detail'){
    $tag=trim($_GET['tag']??'');
    if(!$tag) ha_ok('Missing tag');
    $tag=ltrim($tag,'#');
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND LOWER(p.content) LIKE ? ORDER BY p.created_at DESC LIMIT 20",['%#'.$tag.'%']);
    $totalEng=0;foreach($posts as $p){$totalEng+=intval($p['likes_count'])+intval($p['comments_count']);}
    $avgEng=count($posts)>0?round($totalEng/count($posts),1):0;
    ha_ok('OK',['tag'=>'#'.$tag,'posts'=>$posts,'total_posts'=>count($posts),'total_engagement'=>$totalEng,'avg_engagement'=>$avgEng]);
}

ha_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
