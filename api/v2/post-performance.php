<?php
// ShipperShop API v2 — Post Performance Dashboard
// Comprehensive post analytics: best/worst posts, peak times, content type analysis
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

function pp2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),90);

$data=cache_remember('post_perf_'.$uid.'_'.$days, function() use($d,$uid,$days) {
    $posts=$d->fetchAll("SELECT id,content,likes_count,comments_count,shares_count,image,created_at FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) ORDER BY created_at DESC LIMIT 100",[$uid]);

    if(!count($posts)) return ['posts_count'=>0,'best'=>null,'worst'=>null];

    // Best/worst
    $best=null;$worst=null;$bestEng=0;$worstEng=PHP_INT_MAX;
    $totalEng=0;$withImage=0;$withHashtag=0;$totalLen=0;
    foreach($posts as $p){
        $eng=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
        $totalEng+=$eng;
        if($eng>$bestEng){$bestEng=$eng;$best=['id'=>$p['id'],'preview'=>mb_substr($p['content'],0,60),'engagement'=>$eng];}
        if($eng<$worstEng){$worstEng=$eng;$worst=['id'=>$p['id'],'preview'=>mb_substr($p['content'],0,60),'engagement'=>$eng];}
        if(!empty($p['image'])) $withImage++;
        if(preg_match('/#\w+/u',$p['content'])) $withHashtag++;
        $totalLen+=mb_strlen($p['content']);
    }

    $avgEng=count($posts)>0?round($totalEng/count($posts),1):0;
    $avgLen=count($posts)>0?round($totalLen/count($posts)):0;

    // Image vs no-image engagement
    $imgPosts=array_filter($posts,function($p){return !empty($p['image']);});
    $noImgPosts=array_filter($posts,function($p){return empty($p['image']);});
    $imgAvg=count($imgPosts)>0?round(array_sum(array_map(function($p){return intval($p['likes_count'])+intval($p['comments_count']);},array_values($imgPosts)))/count($imgPosts),1):0;
    $noImgAvg=count($noImgPosts)>0?round(array_sum(array_map(function($p){return intval($p['likes_count'])+intval($p['comments_count']);},array_values($noImgPosts)))/count($noImgPosts),1):0;

    // Peak posting hour
    $hourDist=[];
    foreach($posts as $p){$h=intval(date('H',strtotime($p['created_at'])));$hourDist[$h]=($hourDist[$h]??0)+1;}
    arsort($hourDist);$peakHour=array_key_first($hourDist);

    return ['posts_count'=>count($posts),'avg_engagement'=>$avgEng,'avg_length'=>$avgLen,'best'=>$best,'worst'=>$worst,'image_ratio'=>count($posts)>0?round($withImage/count($posts)*100):0,'hashtag_ratio'=>count($posts)>0?round($withHashtag/count($posts)*100):0,'img_avg_eng'=>$imgAvg,'no_img_avg_eng'=>$noImgAvg,'peak_hour'=>$peakHour,'period_days'=>$days];
}, 600);

pp2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
