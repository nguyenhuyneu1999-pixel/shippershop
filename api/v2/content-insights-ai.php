<?php
// ShipperShop API v2 — Content Insights AI
// AI-powered content analysis: topic extraction, audience match, improvement suggestions
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

$TOPICS=['giao_hang'=>['giao','nhan','don','hang','ship','van chuyen','gui'],'kinh_nghiem'=>['meo','kinh nghiem','chia se','hoc','bai hoc','tip'],'giao_thong'=>['duong','ket xe','cam','phat','toc do','cong an'],'thu_nhap'=>['tien','luong','thu nhap','gia','phi','xang'],'cong_dong'=>['anh em','ban','nhom','hop','giao luu','choi'],'phan_hoi'=>['khach','danh gia','review','tot','xau','kho chiu']];
$AUDIENCES=['shipper_moi'=>['moi','bat dau','lan dau','hoc viec','thuc tap'],'shipper_pro'=>['kinh nghiem','chuyen nghiep','lau nam','ky nang','cao thu'],'quan_ly'=>['quan ly','doi','nhom','tuyen dung','dao tao'],'khach_hang'=>['khach','mua','ban','don hang','ship cho']];

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<15){echo json_encode(['success'=>true,'data'=>['error'=>'Min 15 chars']]);exit;}

$lower=mb_strtolower($text);

// Topic detection
$topicScores=[];
foreach($TOPICS as $topic=>$keywords){
    $score=0;
    foreach($keywords as $kw){if(mb_strpos($lower,$kw)!==false) $score++;}
    if($score>0) $topicScores[$topic]=$score;
}
arsort($topicScores);
$mainTopic=array_key_first($topicScores)?:'general';
$topicLabels=['giao_hang'=>'Giao hang','kinh_nghiem'=>'Kinh nghiem','giao_thong'=>'Giao thong','thu_nhap'=>'Thu nhap','cong_dong'=>'Cong dong','phan_hoi'=>'Phan hoi','general'=>'Tong hop'];

// Audience detection
$audienceScores=[];
foreach($AUDIENCES as $aud=>$keywords){
    $score=0;
    foreach($keywords as $kw){if(mb_strpos($lower,$kw)!==false) $score++;}
    if($score>0) $audienceScores[$aud]=$score;
}
arsort($audienceScores);
$mainAudience=array_key_first($audienceScores)?:'all';
$audLabels=['shipper_moi'=>'Shipper moi','shipper_pro'=>'Shipper pro','quan_ly'=>'Quan ly','khach_hang'=>'Khach hang','all'=>'Tat ca'];

// Suggestions
$suggestions=[];
if(mb_strlen($text)<80) $suggestions[]='Viet dai hon (80+ ky tu) de tang tuong tac';
if(!preg_match('/#\w+/u',$text)) $suggestions[]='Them hashtag (#shipper, #giaohang...)';
if(!preg_match('/\?/u',$text)) $suggestions[]='Dat cau hoi de tang binh luan';
if(!preg_match('/[\x{1F600}-\x{1F9FF}]/u',$text)) $suggestions[]='Them emoji de thu hut';

// Similar top posts
$similar=$d->fetchAll("SELECT id,LEFT(content,60) as preview,likes_count FROM posts WHERE `status`='active' AND LOWER(content) LIKE ? ORDER BY likes_count DESC LIMIT 3",['%'.mb_substr($lower,0,20).'%']);

echo json_encode(['success'=>true,'data'=>['topic'=>$mainTopic,'topic_label'=>$topicLabels[$mainTopic]??'','topic_scores'=>$topicScores,'audience'=>$mainAudience,'audience_label'=>$audLabels[$mainAudience]??'','suggestions'=>$suggestions,'similar_posts'=>$similar,'word_count'=>count(preg_split('/\s+/u',$text))]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
