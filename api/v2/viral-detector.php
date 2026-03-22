<?php
// ShipperShop API v2 — Viral Detector
// Detect posts going viral: engagement velocity, share ratio, growth rate
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$hours=min(intval($_GET['hours']??24),168);

$data=cache_remember('viral_detector_'.$hours, function() use($d,$hours) {
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.shares_count,p.created_at,u.fullname,u.avatar,TIMESTAMPDIFF(HOUR,p.created_at,NOW()) as age_hours FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) AND (p.likes_count+p.comments_count+p.shares_count)>=3 ORDER BY (p.likes_count+p.comments_count+p.shares_count) DESC LIMIT 20");

    $viral=[];
    foreach($posts as $p){
        $engagement=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
        $ageH=max(1,intval($p['age_hours']));
        $velocity=round($engagement/$ageH,2);
        $shareRatio=($engagement>0)?round(intval($p['shares_count'])/$engagement*100,1):0;

        // Viral score: velocity * engagement * share bonus
        $viralScore=round($velocity*sqrt($engagement)*(1+$shareRatio/100));
        $isViral=$viralScore>=5;

        $viral[]=['post_id'=>intval($p['id']),'content'=>mb_substr($p['content'],0,100),'author'=>$p['fullname'],'avatar'=>$p['avatar'],'likes'=>intval($p['likes_count']),'comments'=>intval($p['comments_count']),'shares'=>intval($p['shares_count']),'engagement'=>$engagement,'age_hours'=>$ageH,'velocity'=>$velocity,'share_ratio'=>$shareRatio,'viral_score'=>$viralScore,'is_viral'=>$isViral];
    }

    usort($viral,function($a,$b){return $b['viral_score']-$a['viral_score'];});
    $viralCount=count(array_filter($viral,function($v){return $v['is_viral'];}));

    return ['posts'=>array_slice($viral,0,15),'viral_count'=>$viralCount,'total_analyzed'=>count($viral),'window_hours'=>$hours];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
