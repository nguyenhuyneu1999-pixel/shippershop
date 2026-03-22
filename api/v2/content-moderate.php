<?php
// ShipperShop API v2 — Content Moderation
// Rule-based content screening: spam, profanity, scam detection
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$SPAM_PATTERNS=['casino','lo de','ca cuoc','xo so','slot','danh bai','game doi thuong','tien that'];
$SCAM_PATTERNS=['chuyen tien truoc','ck truoc','nap truoc','100% that','dam bao','cam ket','hoan tien 200%'];
$PROFANITY=['dm','vcl','vkl','dkm','dtm','clm','dit','lon','cac'];

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<5){echo json_encode(['success'=>true,'data'=>['safe'=>true,'score'=>100,'issues'=>[]]]);exit;}

$lower=mb_strtolower($text);
$issues=[];$score=100;

// Spam check
foreach($SPAM_PATTERNS as $p){
    if(mb_strpos($lower,$p)!==false){$issues[]=['type'=>'spam','keyword'=>$p,'severity'=>'high'];$score-=20;}
}

// Scam check
foreach($SCAM_PATTERNS as $p){
    if(mb_strpos($lower,$p)!==false){$issues[]=['type'=>'scam','keyword'=>$p,'severity'=>'critical'];$score-=25;}
}

// Profanity check
$words=preg_split('/[\s,.\-!?]+/u',$lower);
foreach($words as $w){
    if(in_array($w,$PROFANITY)){$issues[]=['type'=>'profanity','keyword'=>$w,'severity'=>'medium'];$score-=15;}
}

// Phone number (potential spam)
if(preg_match_all('/0\d{9,10}/',$text,$phones)&&count($phones[0])>=3){
    $issues[]=['type'=>'phone_spam','detail'=>count($phones[0]).' SDT','severity'=>'medium'];$score-=10;
}

// ALL CAPS (shouting)
$upperRatio=strlen(preg_replace('/[^A-Z]/','',$text))/(strlen(preg_replace('/[^a-zA-Z]/','',$text))?:1);
if($upperRatio>0.6&&strlen($text)>20){$issues[]=['type'=>'caps','detail'=>round($upperRatio*100).'% viet hoa','severity'=>'low'];$score-=5;}

$score=max(0,min(100,$score));
$safe=$score>=60;

echo json_encode(['success'=>true,'data'=>['safe'=>$safe,'score'=>$score,'issues'=>$issues,'issue_count'=>count($issues)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
