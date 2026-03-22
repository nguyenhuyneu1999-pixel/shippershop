<?php
// ShipperShop API v2 — Post Plagiarism Check
// Check if post content is similar to existing posts (duplicate detection)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<20){echo json_encode(['success'=>true,'data'=>['is_original'=>true,'similarity'=>0]]);exit;}

$lower=mb_strtolower($text);
// Extract significant phrases (4+ words chunks)
$words=preg_split('/\s+/u',$lower);
$phrases=[];
for($i=0;$i<count($words)-3;$i++){
    $phrases[]=implode(' ',array_slice($words,$i,4));
}
$phrases=array_slice(array_unique($phrases),0,10);

$matches=[];$maxSim=0;
if($phrases){
    foreach($phrases as $phrase){
        $found=$d->fetchAll("SELECT id,content,user_id FROM posts WHERE `status`='active' AND LOWER(content) LIKE ? LIMIT 3",['%'.$phrase.'%']);
        foreach($found as $f){
            $fLower=mb_strtolower($f['content']);
            // Simple similarity: shared words ratio
            $fWords=array_unique(preg_split('/\s+/u',$fLower));
            $shared=count(array_intersect($words,$fWords));
            $sim=count($words)>0?round($shared/count($words)*100):0;
            if($sim>$maxSim) $maxSim=$sim;
            if($sim>=30&&!isset($matches[$f['id']])){
                $matches[$f['id']]=['post_id'=>intval($f['id']),'similarity'=>$sim,'preview'=>mb_substr($f['content'],0,100)];
            }
        }
    }
}

usort($matches,function($a,$b){return $b['similarity']-$a['similarity'];});
$isOriginal=$maxSim<40;

echo json_encode(['success'=>true,'data'=>['is_original'=>$isOriginal,'max_similarity'=>$maxSim,'matches'=>array_slice(array_values($matches),0,5),'phrases_checked'=>count($phrases)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
