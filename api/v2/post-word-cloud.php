<?php
// ShipperShop API v2 — Post Word Cloud
// Generate word frequency data for word cloud visualization
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$d=db();
try {
$days=min(intval($_GET['days']??7),90);$userId=intval($_GET['user_id']??0);
$data=cache_remember('word_cloud_'.($userId?:'all').'_'.$days, function() use($d,$days,$userId) {
    $where="p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";$params=[];
    if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}
    $posts=$d->fetchAll("SELECT content FROM posts p WHERE $where LIMIT 300",$params);
    $freq=[];$stop=['la','va','cua','cho','trong','voi','nhung','cac','mot','da','duoc','co','khong','den','tu','se','tai','o','di','ve','nha','em','anh','chi','nhe','a','ma','thi','nay','day','roi'];
    foreach($posts as $p){
        $words=preg_split('/[\s,.\-!?:;]+/u',mb_strtolower($p['content']??''));
        foreach($words as $w){$w=trim($w,'#@');if(mb_strlen($w)>=2&&!in_array($w,$stop)){$freq[$w]=($freq[$w]??0)+1;}}
    }
    arsort($freq);$cloud=[];
    foreach(array_slice($freq,0,50,true) as $w=>$c){$cloud[]=['word'=>$w,'count'=>$c,'size'=>min(36,max(12,round(log($c+1)*8)))];}
    return ['cloud'=>$cloud,'total_words'=>array_sum($freq),'unique_words'=>count($freq),'posts_scanned'=>count($posts)];
}, 600);
echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
