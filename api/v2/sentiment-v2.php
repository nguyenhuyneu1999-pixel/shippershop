<?php
// ShipperShop API v2 — Post Sentiment V2
// Advanced sentiment analysis: emotion detection, toxicity score, mood tracking
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

$POSITIVE=['vui','hay','tot','tuyet voi','cam on','yeu','thich','dep','gioi','sieu','xin','ok','nice','good','great','happy','thanks','love','amazing'];
$NEGATIVE=['buon','chan','te','xau','ghet','kho chiu','toi te','ngu','do','loi','hong','hu','mat','chet','kho','met','stress','bad','hate','angry','sad'];
$EMOTIONS=['joy'=>['vui','ha ha','hihi','yeah','wow','tuyet','suong','phe'],'anger'=>['tuc','dien','ghet','ngu','do','met moi','chan ngat'],'sadness'=>['buon','khoc','nho','co don','that vong','tiec'],'fear'=>['so','lo','nguy hiem','canh bao','can than','nguy'],'surprise'=>['bat ngo','wow','oi','troi','that a','khong the tin']];

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<5){echo json_encode(['success'=>true,'data'=>['sentiment'=>'neutral','score'=>0]]);exit;}

$lower=mb_strtolower($text);
$posCount=0;$negCount=0;
foreach($POSITIVE as $w){if(mb_strpos($lower,$w)!==false) $posCount++;}
foreach($NEGATIVE as $w){if(mb_strpos($lower,$w)!==false) $negCount++;}

$sentiment=$posCount>$negCount?'positive':($negCount>$posCount?'negative':'neutral');
$score=$posCount-$negCount;
$confidence=min(95,50+abs($score)*15);

// Emotion detection
$emotionScores=[];
foreach($EMOTIONS as $emo=>$keywords){
    $emoScore=0;
    foreach($keywords as $kw){if(mb_strpos($lower,$kw)!==false) $emoScore++;}
    if($emoScore>0) $emotionScores[$emo]=$emoScore;
}
arsort($emotionScores);
$primaryEmotion=array_key_first($emotionScores)?:'neutral';
$emoIcons=['joy'=>'😊','anger'=>'😠','sadness'=>'😢','fear'=>'😰','surprise'=>'😲','neutral'=>'😐'];

// Toxicity check
$toxicWords=['ngu','do','mat day','lon','dit','dm','vcl','cc'];
$toxicCount=0;
foreach($toxicWords as $tw){if(mb_strpos($lower,$tw)!==false) $toxicCount++;}
$toxicityScore=min(100,$toxicCount*25);

echo json_encode(['success'=>true,'data'=>['sentiment'=>$sentiment,'score'=>$score,'confidence'=>$confidence,'positive_count'=>$posCount,'negative_count'=>$negCount,'primary_emotion'=>$primaryEmotion,'emotion_icon'=>$emoIcons[$primaryEmotion]??'😐','emotion_scores'=>$emotionScores,'toxicity'=>$toxicityScore,'is_toxic'=>$toxicityScore>=50]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
