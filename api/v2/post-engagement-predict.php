<?php
// ShipperShop API v2 — Post Engagement Predict V3
// Predict engagement before posting using content analysis + historical data
session_start();
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
$hasImage=!empty($_GET['has_image']||($input['has_image']??false));
$hour=intval($_GET['hour']??date('H'));

if(!$text){echo json_encode(['success'=>true,'data'=>['predicted_likes'=>0,'predicted_comments'=>0]]);exit;}

// Base scores from content features
$len=mb_strlen($text);$lenScore=$len>=80?1.5:($len>=40?1.0:0.6);
$imageScore=$hasImage?1.8:1.0;
$hashtagCount=preg_match_all('/#\w+/u',$text);
$hashScore=$hashtagCount>=1&&$hashtagCount<=3?1.3:1.0;
$questionScore=strpos($text,'?')!==false?1.4:1.0;
$emojiScore=preg_match('/[\x{1F600}-\x{1F9FF}]/u',$text)?1.2:1.0;

// Hour multiplier (peak: 11-13, 18-21)
$hourMult=1.0;
if($hour>=11&&$hour<=13) $hourMult=1.4;
elseif($hour>=18&&$hour<=21) $hourMult=1.6;
elseif($hour>=7&&$hour<=9) $hourMult=1.2;
elseif($hour>=0&&$hour<=5) $hourMult=0.4;

// Platform baseline
$avgLikes=floatval($d->fetchOne("SELECT AVG(likes_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??2);
$avgComments=floatval($d->fetchOne("SELECT AVG(comments_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??1);

$predictedLikes=round($avgLikes*$lenScore*$imageScore*$hashScore*$hourMult*$emojiScore);
$predictedComments=round($avgComments*$lenScore*$questionScore*$hourMult);
$predictedShares=round($predictedLikes*0.15);
$confidence=min(85,50+($len>80?10:0)+($hasImage?15:0)+($hashtagCount>0?5:0)+($hour>=7&&$hour<=21?5:0));

$factors=[];
if($hasImage) $factors[]=['name'=>'Hinh anh','impact'=>'+80%','positive'=>true];
if($hashtagCount>=1) $factors[]=['name'=>'Hashtag','impact'=>'+30%','positive'=>true];
if(strpos($text,'?')!==false) $factors[]=['name'=>'Cau hoi','impact'=>'+40%','positive'=>true];
if($hourMult>=1.4) $factors[]=['name'=>'Gio vang','impact'=>'+'.round(($hourMult-1)*100).'%','positive'=>true];
if($hourMult<0.8) $factors[]=['name'=>'Gio thap','impact'=>round(($hourMult-1)*100).'%','positive'=>false];
if($len<40) $factors[]=['name'=>'Ngan','impact'=>'-40%','positive'=>false];

echo json_encode(['success'=>true,'data'=>['predicted_likes'=>max(0,$predictedLikes),'predicted_comments'=>max(0,$predictedComments),'predicted_shares'=>max(0,$predictedShares),'total_engagement'=>$predictedLikes+$predictedComments+$predictedShares,'confidence'=>$confidence,'factors'=>$factors,'hour'=>$hour,'baseline'=>['avg_likes'=>round($avgLikes,1),'avg_comments'=>round($avgComments,1)]]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
