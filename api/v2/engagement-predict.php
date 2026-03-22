<?php
// ShipperShop API v2 — Post Engagement Predictor
// Predict expected engagement based on content analysis + historical data
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function ep_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<10) ep_ok('OK',['score'=>50,'prediction'=>'trung binh']);

// Factors
$score=50;$factors=[];

// Length bonus (optimal 100-300 chars)
$len=mb_strlen($text);
if($len>=100&&$len<=300){$score+=10;$factors[]=['name'=>'Do dai tot','impact'=>'+10','desc'=>$len.' ky tu (100-300 ly tuong)'];}
elseif($len>300){$score+=5;$factors[]=['name'=>'Bai dai','impact'=>'+5','desc'=>$len.' ky tu'];}
else{$score-=5;$factors[]=['name'=>'Bai ngan','impact'=>'-5','desc'=>$len.' ky tu (nen >100)'];}

// Hashtag bonus
preg_match_all('/#\w+/u',$text,$hm);
$hashCount=count($hm[0]??[]);
if($hashCount>=1&&$hashCount<=3){$score+=8;$factors[]=['name'=>'Hashtags','impact'=>'+8','desc'=>$hashCount.' hashtag'];}
elseif($hashCount>3){$score+=3;$factors[]=['name'=>'Qua nhieu hashtag','impact'=>'+3','desc'=>$hashCount.' (nen 1-3)'];}

// Emoji bonus
if(preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F900}-\x{1F9FF}]/u',$text)){$score+=5;$factors[]=['name'=>'Co emoji','impact'=>'+5','desc'=>'Emoji tang tuong tac'];}

// Question = higher engagement
if(mb_strpos($text,'?')!==false){$score+=7;$factors[]=['name'=>'Dat cau hoi','impact'=>'+7','desc'=>'Cau hoi thu hut binh luan'];}

// Location mention
if(preg_match('/tinh|thanh pho|quan|huyen|tphcm|ha noi|sai gon|da nang/ui',$text)){$score+=5;$factors[]=['name'=>'Dia diem','impact'=>'+5','desc'=>'Nhac dia diem tang tuong tac'];}

// Time factor (posting now)
$hour=intval(date('H'));
if($hour>=7&&$hour<=9){$score+=8;$factors[]=['name'=>'Gio vang sang','impact'=>'+8','desc'=>'7-9h la gio cao diem'];}
elseif($hour>=17&&$hour<=19){$score+=8;$factors[]=['name'=>'Gio vang chieu','impact'=>'+8','desc'=>'17-19h la gio cao diem'];}
elseif($hour>=12&&$hour<=13){$score+=5;$factors[]=['name'=>'Gio trua','impact'=>'+5','desc'=>'12-13h kha tot'];}
elseif($hour>=23||$hour<=5){$score-=10;$factors[]=['name'=>'Gio khuya','impact'=>'-10','desc'=>'Gio nay it nguoi xem'];}

$score=max(10,min(100,$score));
$prediction=$score>=80?'rat cao':($score>=60?'cao':($score>=40?'trung binh':'thap'));

// Platform averages
$avgLikes=floatval($d->fetchOne("SELECT AVG(likes_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??0);
$avgComments=floatval($d->fetchOne("SELECT AVG(comments_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??0);
$expectedLikes=round($avgLikes*$score/50,1);
$expectedComments=round($avgComments*$score/50,1);

ep_ok('OK',['score'=>$score,'prediction'=>$prediction,'expected_likes'=>$expectedLikes,'expected_comments'=>$expectedComments,'factors'=>$factors,'current_hour'=>$hour]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
