<?php
// ShipperShop API v2 — Admin Content Quality
// Platform-wide content quality metrics: avg length, image ratio, spam rate, top quality
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

function acq_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function acq_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') acq_fail('Admin only',403);

$data=cache_remember('admin_content_quality', function() use($d) {
    $posts=$d->fetchAll("SELECT content,image,likes_count,comments_count FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 500");
    $totalLen=0;$withImage=0;$withHashtag=0;$short=0;$totalEng=0;
    foreach($posts as $p){
        $len=mb_strlen($p['content']??'');$totalLen+=$len;
        if(!empty($p['image'])) $withImage++;
        if(preg_match('/#\w+/u',$p['content']??'')) $withHashtag++;
        if($len<30) $short++;
        $totalEng+=intval($p['likes_count'])+intval($p['comments_count']);
    }
    $c=max(1,count($posts));
    $avgLen=round($totalLen/$c);$imgRatio=round($withImage/$c*100,1);
    $hashRatio=round($withHashtag/$c*100,1);$shortRatio=round($short/$c*100,1);
    $avgEng=round($totalEng/$c,1);
    $qualityScore=min(100,round(($avgLen>60?30:$avgLen*0.5)+($imgRatio>30?25:$imgRatio*0.8)+($hashRatio>20?15:$hashRatio*0.75)+(100-$shortRatio)*0.2+min($avgEng*3,10)));
    $grade=$qualityScore>=75?'A':($qualityScore>=55?'B':($qualityScore>=35?'C':'D'));
    return ['quality_score'=>$qualityScore,'grade'=>$grade,'avg_length'=>$avgLen,'image_ratio'=>$imgRatio,'hashtag_ratio'=>$hashRatio,'short_ratio'=>$shortRatio,'avg_engagement'=>$avgEng,'posts_analyzed'=>$c];
}, 1800);

acq_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
