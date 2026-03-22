<?php
// ShipperShop API v2 — Plagiarism Check V2
// Advanced duplicate detection: n-gram matching, similarity score, source identification
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<20){echo json_encode(['success'=>true,'data'=>['original'=>true,'score'=>100,'matches'=>[]]]);exit;}

$lower=mb_strtolower($text);
$words=preg_split('/\s+/u',$lower);

// Generate n-grams (3-word phrases)
$ngrams=[];
for($i=0;$i<count($words)-2;$i++){$ngrams[]=implode(' ',array_slice($words,$i,3));}

// Search recent posts for matching n-grams
$recentPosts=$d->fetchAll("SELECT id,content,user_id FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY created_at DESC LIMIT 200");

$matches=[];$totalMatched=0;
foreach($recentPosts as $p){
    $pLower=mb_strtolower($p['content']??'');
    $matchedNgrams=0;
    foreach($ngrams as $ng){
        if(mb_strpos($pLower,$ng)!==false) $matchedNgrams++;
    }
    if($matchedNgrams>=2&&count($ngrams)>0){
        $similarity=round($matchedNgrams/count($ngrams)*100,1);
        if($similarity>=15){
            $matches[]=['post_id'=>intval($p['id']),'similarity'=>$similarity,'matched_phrases'=>$matchedNgrams,'preview'=>mb_substr($p['content'],0,80)];
            $totalMatched+=$matchedNgrams;
        }
    }
}

usort($matches,function($a,$b){return $b['similarity']-$a['similarity'];});
$matches=array_slice($matches,0,5);

$originalityScore=count($ngrams)>0?max(0,100-round($totalMatched/count($ngrams)*50)):100;
$isOriginal=$originalityScore>=70;

echo json_encode(['success'=>true,'data'=>['original'=>$isOriginal,'score'=>$originalityScore,'matches'=>$matches,'ngrams_checked'=>count($ngrams),'posts_scanned'=>count($recentPosts)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
