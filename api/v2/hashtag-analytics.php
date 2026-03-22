<?php
// ShipperShop API v2 — Hashtag Analytics
// Track hashtag performance: usage frequency, engagement, trending
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

try {

$days=min(intval($_GET['days']??30),365);

if(!$action||$action==='top'){
    $data=cache_remember('hashtag_analytics_'.$days, function() use($d,$days) {
        $posts=$d->fetchAll("SELECT id,content,likes_count,comments_count,shares_count FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) LIMIT 500");
        $hashStats=[];
        foreach($posts as $p){
            preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$p['content']??'',$m);
            $eng=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
            foreach(($m[1]??[]) as $h){
                $h=mb_strtolower($h);
                if(!isset($hashStats[$h])) $hashStats[$h]=['tag'=>'#'.$h,'posts'=>0,'total_engagement'=>0,'post_ids'=>[]];
                $hashStats[$h]['posts']++;
                $hashStats[$h]['total_engagement']+=$eng;
                $hashStats[$h]['post_ids'][]=$p['id'];
            }
        }
        foreach($hashStats as &$hs){
            $hs['avg_engagement']=$hs['posts']>0?round($hs['total_engagement']/$hs['posts'],1):0;
            $hs['post_ids']=array_slice($hs['post_ids'],0,5);
        }unset($hs);
        usort($hashStats,function($a,$b){return $b['posts']-$a['posts'];});
        return ['hashtags'=>array_slice(array_values($hashStats),0,30),'total_unique'=>count($hashStats),'posts_analyzed'=>count($posts),'period_days'=>$days];
    }, 600);
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;
}

// Single hashtag detail
if($action==='detail'){
    $tag=mb_strtolower(trim($_GET['tag']??''));
    $tag=ltrim($tag,'#');
    if(!$tag){echo json_encode(['success'=>true,'data'=>null]);exit;}
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND LOWER(p.content) LIKE ? ORDER BY p.created_at DESC LIMIT 10",['%#'.$tag.'%']);
    $totalEng=0;foreach($posts as $p){$totalEng+=intval($p['likes_count'])+intval($p['comments_count']);}
    echo json_encode(['success'=>true,'data'=>['tag'=>'#'.$tag,'posts'=>$posts,'post_count'=>count($posts),'total_engagement'=>$totalEng]],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
