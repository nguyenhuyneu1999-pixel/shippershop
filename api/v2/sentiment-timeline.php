<?php
// ShipperShop API v2 — Sentiment Timeline
// Track post sentiment over time: positive/negative/neutral distribution
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

$POS_WORDS=['tot','tuyet','vui','cam on','yeu','dep','nhanh','chuyen nghiep','an toan','tin cay','hat luong','nhiet tinh','oke','ok','dung gio','suon'];
$NEG_WORDS=['te','chan','buon','gian','loi','cham','huy','mat','xau','that bai','phot','bo','khong','kho','nan','dau'];

try {

$days=min(intval($_GET['days']??14),90);
$userId=intval($_GET['user_id']??0);

$data=cache_remember('sentiment_tl_'.($userId?:'all').'_'.$days, function() use($d,$days,$userId,$POS_WORDS,$NEG_WORDS) {
    $where="p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
    $params=[];
    if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}

    $posts=$d->fetchAll("SELECT DATE(p.created_at) as day, p.content FROM posts p WHERE $where ORDER BY p.created_at",$params);

    $daily=[];
    foreach($posts as $p){
        $day=$p['day'];
        if(!isset($daily[$day])) $daily[$day]=['day'=>$day,'positive'=>0,'negative'=>0,'neutral'=>0,'total'=>0];
        $lower=mb_strtolower($p['content']??'');
        $pos=0;$neg=0;
        foreach($POS_WORDS as $w){if(mb_strpos($lower,$w)!==false) $pos++;}
        foreach($NEG_WORDS as $w){if(mb_strpos($lower,$w)!==false) $neg++;}
        if($pos>$neg) $daily[$day]['positive']++;
        elseif($neg>$pos) $daily[$day]['negative']++;
        else $daily[$day]['neutral']++;
        $daily[$day]['total']++;
    }

    ksort($daily);
    $timeline=array_values($daily);
    $totalPos=array_sum(array_column($timeline,'positive'));
    $totalNeg=array_sum(array_column($timeline,'negative'));
    $totalNeu=array_sum(array_column($timeline,'neutral'));
    $total=$totalPos+$totalNeg+$totalNeu;
    $mood=$totalPos>$totalNeg?'positive':($totalNeg>$totalPos?'negative':'neutral');

    return ['timeline'=>$timeline,'summary'=>['positive'=>$totalPos,'negative'=>$totalNeg,'neutral'=>$totalNeu,'total'=>$total,'mood'=>$mood,'positive_pct'=>$total>0?round($totalPos/$total*100):0]];
}, 600);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
