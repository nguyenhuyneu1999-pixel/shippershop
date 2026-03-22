<?php
// ShipperShop API v2 — Content Quality Gate
// Pre-publish quality check: length, images, hashtags, readability, spam score
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
$hasImage=!empty($_GET['has_image']||($input['has_image']??false));

if(!$text){echo json_encode(['success'=>true,'data'=>['pass'=>false,'score'=>0,'checks'=>[]]]);exit;}

$checks=[];$score=100;

// Length check
$len=mb_strlen($text);
if($len<20){$checks[]=['name'=>'Do dai','status'=>'fail','detail'=>$len.' ky tu (min 20)','impact'=>-20];$score-=20;}
elseif($len<50){$checks[]=['name'=>'Do dai','status'=>'warn','detail'=>$len.' ky tu (khuyen 50+)','impact'=>-5];$score-=5;}
else{$checks[]=['name'=>'Do dai','status'=>'pass','detail'=>$len.' ky tu','impact'=>0];}

// Image check
if($hasImage){$checks[]=['name'=>'Hinh anh','status'=>'pass','detail'=>'Co hinh anh','impact'=>0];}
else{$checks[]=['name'=>'Hinh anh','status'=>'warn','detail'=>'Khong co hinh (khuyen them)','impact'=>-10];$score-=10;}

// Hashtags
preg_match_all('/#\w+/u',$text,$hm);$hc=count($hm[0]??[]);
if($hc>=1&&$hc<=5){$checks[]=['name'=>'Hashtag','status'=>'pass','detail'=>$hc.' hashtag','impact'=>0];}
elseif($hc===0){$checks[]=['name'=>'Hashtag','status'=>'warn','detail'=>'Khong co hashtag','impact'=>-5];$score-=5;}
else{$checks[]=['name'=>'Hashtag','status'=>'warn','detail'=>$hc.' hashtag (nhieu qua)','impact'=>-5];$score-=5;}

// Spam patterns
$spamWords=['casino','lo de','ca cuoc','chuyen tien truoc','dam bao 100%'];
$spamCount=0;$lower=mb_strtolower($text);
foreach($spamWords as $sw){if(mb_strpos($lower,$sw)!==false) $spamCount++;}
if($spamCount>0){$checks[]=['name'=>'Spam','status'=>'fail','detail'=>$spamCount.' tu spam','impact'=>-30];$score-=30;}
else{$checks[]=['name'=>'Spam','status'=>'pass','detail'=>'Sach','impact'=>0];}

// ALL CAPS
$upperRatio=strlen(preg_replace('/[^A-Z]/','',$text))/(strlen(preg_replace('/[^a-zA-Z]/','',$text))?:1);
if($upperRatio>0.6&&strlen($text)>20){$checks[]=['name'=>'Viet hoa','status'=>'warn','detail'=>round($upperRatio*100).'% viet hoa','impact'=>-10];$score-=10;}
else{$checks[]=['name'=>'Viet hoa','status'=>'pass','detail'=>'Binh thuong','impact'=>0];}

// Phone number count
preg_match_all('/0\d{9,10}/',$text,$phones);
if(count($phones[0])>=3){$checks[]=['name'=>'SDT','status'=>'warn','detail'=>count($phones[0]).' so dien thoai','impact'=>-5];$score-=5;}

$score=max(0,min(100,$score));
$pass=$score>=60;
$grade=$score>=90?'A':($score>=70?'B':($score>=50?'C':'D'));

echo json_encode(['success'=>true,'data'=>['pass'=>$pass,'score'=>$score,'grade'=>$grade,'checks'=>$checks,'total_checks'=>count($checks)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
