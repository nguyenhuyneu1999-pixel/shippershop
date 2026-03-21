<?php
// ShipperShop API v2 — Post Sentiment Analysis
// Simple keyword-based sentiment scoring for posts
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function ps2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$POS=['thanh cong','tot','tuyet voi','yeu','cam on','hay','dep','vui','hanh phuc','like','love','ung ho','gioi','pro','xuat sac','nhanh','ok','good','great','nice','perfect','happy','awesome','amazing'];
$NEG=['that bai','te','chan','buon','gian','tuc','sai','loi','cham','hu','hong','met','kho chiu','xau','kem','bad','sad','angry','hate','slow','broken','fail','worst','terrible'];

function analyzeSentiment($text,$pos,$neg){
    $text=mb_strtolower($text);$score=0;$posCount=0;$negCount=0;$matches=[];
    foreach($pos as $w){if(mb_strpos($text,$w)!==false){$posCount++;$score++;$matches[]=['word'=>$w,'type'=>'positive'];}}
    foreach($neg as $w){if(mb_strpos($text,$w)!==false){$negCount++;$score--;$matches[]=['word'=>$w,'type'=>'negative'];}}
    $label=$score>0?'positive':($score<0?'negative':'neutral');
    return ['score'=>$score,'label'=>$label,'positive_count'=>$posCount,'negative_count'=>$negCount,'matches'=>$matches];
}

try {

$action=$_GET['action']??'';

// Analyze single post
if(!$action){
    $postId=intval($_GET['post_id']??0);
    $text=$_GET['text']??'';
    if($postId){
        $post=$d->fetchOne("SELECT content FROM posts WHERE id=? AND `status`='active'",[$postId]);
        $text=$post?$post['content']:'';
    }
    if(!$text) ps2_ok('OK',['score'=>0,'label'=>'neutral']);
    $result=analyzeSentiment($text,$POS,$NEG);
    $result['text_length']=mb_strlen($text);
    ps2_ok('OK',$result);
}

// Platform sentiment overview
if($action==='overview'){
    $data=cache_remember('sentiment_overview', function() use($d,$POS,$NEG) {
        $posts=$d->fetchAll("SELECT id,content FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 100");
        $pos=0;$neg=0;$neu=0;$totalScore=0;
        foreach($posts as $p){
            $s=analyzeSentiment($p['content'],$POS,$NEG);
            if($s['label']==='positive') $pos++;
            elseif($s['label']==='negative') $neg++;
            else $neu++;
            $totalScore+=$s['score'];
        }
        $total=count($posts);
        return ['total_posts'=>$total,'positive'=>$pos,'negative'=>$neg,'neutral'=>$neu,'avg_score'=>$total>0?round($totalScore/$total,2):0,'positive_pct'=>$total>0?round($pos/$total*100,1):0];
    }, 600);
    ps2_ok('OK',$data);
}

ps2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
