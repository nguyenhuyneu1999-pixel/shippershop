<?php
// ShipperShop API v2 — Content Recycler
// Find and suggest old high-performing posts to repost/update
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

$d=db();

function cr3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

$data=cache_remember('content_recycler_'.$uid, function() use($d,$uid) {
    // High-performing old posts (30+ days, above avg engagement)
    $avgEng=floatval($d->fetchOne("SELECT AVG(likes_count+comments_count) as a FROM posts WHERE user_id=? AND `status`='active'",[$uid])['a']??0);
    $candidates=$d->fetchAll("SELECT id,LEFT(content,100) as preview,likes_count,comments_count,shares_count,image,created_at FROM posts WHERE user_id=? AND `status`='active' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND (likes_count+comments_count) > ? ORDER BY (likes_count+comments_count) DESC LIMIT 10",[$uid,max(1,$avgEng)]);

    foreach($candidates as &$c){
        $daysSince=intval((time()-strtotime($c['created_at']))/86400);
        $eng=intval($c['likes_count'])+intval($c['comments_count'])+intval($c['shares_count']);
        $c['days_since']=$daysSince;
        $c['engagement']=$eng;
        $c['recycle_score']=round($eng*0.6+$daysSince*0.2+(empty($c['image'])?0:15));
        // Suggestion
        if($daysSince>90) $c['suggestion']='Repost voi cap nhat moi';
        elseif($daysSince>60) $c['suggestion']='Chia se lai voi goc nhin moi';
        else $c['suggestion']='Them hinh/hashtag roi dang lai';
    }unset($c);

    usort($candidates,function($a,$b){return $b['recycle_score']-$a['recycle_score'];});

    return ['candidates'=>$candidates,'avg_engagement'=>round($avgEng,1),'count'=>count($candidates)];
}, 3600);

cr3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
