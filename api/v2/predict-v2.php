<?php
// ShipperShop API v2 — Engagement Predictor V2
// Advanced prediction using post characteristics + historical patterns
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
$postHour=intval($_GET['hour']??date('H'));
$userId=intval($_GET['user_id']??0);

if(!$text||mb_strlen($text)<10){echo json_encode(['success'=>true,'data'=>['score'=>0,'prediction'=>'N/A']]);exit;}

$score=50;$factors=[];

// Text quality
$len=mb_strlen($text);
if($len>=80&&$len<=500){$score+=8;$factors[]=['factor'=>'Do dai tot','impact'=>'+8'];}
elseif($len>500){$score+=4;$factors[]=['factor'=>'Bai dai','impact'=>'+4'];}

// Image bonus
if($hasImage){$score+=15;$factors[]=['factor'=>'Co hinh anh','impact'=>'+15'];}

// Hashtags
preg_match_all('/#\w+/u',$text,$hm);
$hc=count($hm[0]??[]);
if($hc>=1&&$hc<=3){$score+=8;$factors[]=['factor'=>'Hashtag ('.$hc.')','impact'=>'+8'];}

// Question (engagement driver)
if(mb_strpos($text,'?')!==false){$score+=10;$factors[]=['factor'=>'Cau hoi','impact'=>'+10'];}

// Emoji
if(preg_match('/[\x{1F600}-\x{1F9FF}]/u',$text)){$score+=5;$factors[]=['factor'=>'Emoji','impact'=>'+5'];}

// Time factor
$peakHours=[8,12,18,20,21,22];
if(in_array($postHour,$peakHours)){$score+=8;$factors[]=['factor'=>'Gio cao diem ('.$postHour.'h)','impact'=>'+8'];}

// User history factor
if($userId){
    $avgEng=$d->fetchOne("SELECT AVG(likes_count+comments_count) as a FROM posts WHERE user_id=? AND `status`='active' ORDER BY id DESC LIMIT 10",[$userId]);
    $userAvg=floatval($avgEng['a']??0);
    if($userAvg>5){$score+=10;$factors[]=['factor'=>'User uy tin (TB '.round($userAvg,1).')','impact'=>'+10'];}
}

// Location mention
if(preg_match('/quan|huyen|tphcm|ha noi|da nang|sai gon/ui',$text)){$score+=5;$factors[]=['factor'=>'Dia diem','impact'=>'+5'];}

$score=min(100,max(0,$score));
$predictions=['0-30'=>'Thap','31-50'=>'Trung binh','51-70'=>'Kha','71-85'=>'Tot','86-100'=>'Rat tot'];
$prediction='Trung binh';
foreach($predictions as $range=>$label){
    $parts=explode('-',$range);
    if($score>=intval($parts[0])&&$score<=intval($parts[1])){$prediction=$label;break;}
}

$estLikes=round($score*0.3);$estComments=round($score*0.1);

echo json_encode(['success'=>true,'data'=>['score'=>$score,'prediction'=>$prediction,'estimated_likes'=>$estLikes,'estimated_comments'=>$estComments,'factors'=>$factors,'tips'=>$score<70?['Them hinh anh','Dat cau hoi','Dang gio cao diem','Them hashtag']:[]]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
