<?php
// ShipperShop API v2 — Content Quality Score
// Score post quality: length, media, hashtags, engagement prediction
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$postId=intval($_GET['post_id']??0);
$text=trim($_GET['text']??'');
if($postId){$p=$d->fetchOne("SELECT content,likes_count,comments_count,shares_count FROM posts WHERE id=? AND `status`='active'",[$postId]);if($p)$text=$p['content'];}

if(!$text||mb_strlen($text)<5){echo json_encode(['success'=>true,'data'=>['score'=>0,'grade'=>'N/A']]);exit;}

$score=0;$factors=[];

// Length (optimal 80-400)
$len=mb_strlen($text);
if($len>=80&&$len<=400){$score+=25;$factors[]=['name'=>'Do dai tot','pts'=>25];}
elseif($len>=40){$score+=15;$factors[]=['name'=>'Do dai kha','pts'=>15];}
else{$score+=5;$factors[]=['name'=>'Qua ngan','pts'=>5];}

// Paragraphs/line breaks
$lines=substr_count($text,"\n")+1;
if($lines>=2){$score+=10;$factors[]=['name'=>'Co dinh dang','pts'=>10];}

// Hashtags
preg_match_all('/#\w+/u',$text,$hm);
$hc=count($hm[0]??[]);
if($hc>=1&&$hc<=5){$score+=15;$factors[]=['name'=>'Hashtags ('.$hc.')','pts'=>15];}
elseif($hc>5){$score+=5;$factors[]=['name'=>'Qua nhieu hashtag','pts'=>5];}

// Emoji
if(preg_match('/[\x{1F600}-\x{1F9FF}]/u',$text)){$score+=10;$factors[]=['name'=>'Co emoji','pts'=>10];}

// Location mention
if(preg_match('/tinh|quan|huyen|tphcm|ha noi|sai gon|da nang/ui',$text)){$score+=10;$factors[]=['name'=>'Co dia diem','pts'=>10];}

// Question (engagement driver)
if(mb_strpos($text,'?')!==false){$score+=10;$factors[]=['name'=>'Cau hoi','pts'=>10];}

// Call to action
if(preg_match('/lien he|inbox|dm|goi|sdt|so dien thoai/ui',$text)){$score+=10;$factors[]=['name'=>'Call to action','pts'=>10];}

// Shipping keyword relevance
if(preg_match('/ship|giao hang|don hang|cod|van chuyen|shipper/ui',$text)){$score+=10;$factors[]=['name'=>'Lien quan shipper','pts'=>10];}

$score=min(100,$score);
$grades=[90=>'A+',80=>'A',70=>'B+',60=>'B',50=>'C+',40=>'C',0=>'D'];
$grade='D';foreach($grades as $min=>$g){if($score>=$min){$grade=$g;break;}}

echo json_encode(['success'=>true,'data'=>['score'=>$score,'grade'=>$grade,'factors'=>$factors,'text_length'=>$len]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
