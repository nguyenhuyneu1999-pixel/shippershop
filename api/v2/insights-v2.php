<?php
// ShipperShop API v2 — Content Insights V2
// Deep post performance: reach, engagement rate, best time, audience
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$postId=intval($_GET['post_id']??0);
$userId=intval($_GET['user_id']??0);

if($postId){
    $p=$d->fetchOne("SELECT p.*,u.fullname,u.total_posts FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$postId]);
    if(!$p){echo json_encode(['success'=>true,'data'=>null]);exit;}
    $engagement=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
    $engRate=$p['view_count']>0?round($engagement/intval($p['view_count'])*100,1):0;
    $hourPosted=intval(date('H',strtotime($p['created_at'])));
    $dayPosted=date('l',strtotime($p['created_at']));
    $ageHours=round((time()-strtotime($p['created_at']))/3600,1);
    $engPerHour=$ageHours>0?round($engagement/$ageHours,2):0;

    echo json_encode(['success'=>true,'data'=>['post_id'=>$postId,'author'=>$p['fullname'],'likes'=>intval($p['likes_count']),'comments'=>intval($p['comments_count']),'shares'=>intval($p['shares_count']),'views'=>intval($p['view_count']),'engagement'=>$engagement,'engagement_rate'=>$engRate,'eng_per_hour'=>$engPerHour,'hour_posted'=>$hourPosted,'day_posted'=>$dayPosted,'age_hours'=>$ageHours,'content_length'=>mb_strlen($p['content'])]],JSON_UNESCAPED_UNICODE);exit;
}

if($userId){
    $data=cache_remember('insights_v2_user_'.$userId, function() use($d,$userId) {
        $posts=$d->fetchAll("SELECT likes_count,comments_count,shares_count,view_count,created_at FROM posts WHERE user_id=? AND `status`='active' ORDER BY created_at DESC LIMIT 30",[$userId]);
        $totalEng=0;$totalViews=0;$bestPost=null;$bestEng=0;
        foreach($posts as $p){
            $eng=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
            $totalEng+=$eng;$totalViews+=intval($p['view_count']);
            if($eng>$bestEng){$bestEng=$eng;$bestPost=$p;}
        }
        $avgEng=count($posts)>0?round($totalEng/count($posts),1):0;
        $avgEngRate=$totalViews>0?round($totalEng/$totalViews*100,1):0;
        return ['posts_analyzed'=>count($posts),'total_engagement'=>$totalEng,'avg_engagement'=>$avgEng,'avg_engagement_rate'=>$avgEngRate,'best_engagement'=>$bestEng];
    }, 600);
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
