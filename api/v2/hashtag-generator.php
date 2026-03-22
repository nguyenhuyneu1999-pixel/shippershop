<?php
// ShipperShop API v2 — Hashtag Generator
// Tinh nang: Tu dong tao hashtag phu hop tu noi dung bai viet
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

$HASHTAG_MAP=['giao hang'=>['#giaohang','#ship','#delivery'],'shipper'=>['#shipper','#shipperlife','#taixe'],'ghtk'=>['#ghtk','#giaohangtietkiem'],'ghn'=>['#ghn','#giaohangnhanh'],'spx'=>['#spx','#shopeeexpress','#shopee'],'j&t'=>['#jt','#jtexpress'],'viettel'=>['#viettelpost','#viettel'],'grab'=>['#grab','#grabexpress'],'be'=>['#be','#bedelivery'],'mua'=>['#muaban','#choshipper'],'xe'=>['#xemay','#honda','#yamaha'],'xang'=>['#giaxang','#xang','#titkiem'],'duong'=>['#giaothong','#duong','#ketxe'],'mua'=>['#muaban','#raovat'],'tip'=>['#meo','#kinhnghiem','#tips'],'tphcm'=>['#tphcm','#saigon','#hcm'],'hanoi'=>['#hanoi','#hn'],'danang'=>['#danang','#dn']];

$TRENDING_TAGS=['#shipper','#giaohang','#shippershop','#congdongshipper','#shipperlife','#ghtk','#ghn','#spx','#taixe','#giaohangtiencong'];

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text){echo json_encode(['success'=>true,'data'=>['hashtags'=>$TRENDING_TAGS,'source'=>'trending']]);exit;}

$lower=mb_strtolower($text);
$suggested=[];$reasons=[];

// Match from map
foreach($HASHTAG_MAP as $keyword=>$tags){
    if(mb_strpos($lower,$keyword)!==false){
        foreach($tags as $tag){
            if(!in_array($tag,$suggested)){$suggested[]=$tag;$reasons[$tag]='keyword: '.$keyword;}
        }
    }
}

// Extract existing hashtags
preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$text,$matches);
$existing=array_map(function($m){return '#'.mb_strtolower($m);},$matches[1]??[]);

// Add trending if few matches
if(count($suggested)<3){
    foreach($TRENDING_TAGS as $tt){
        if(!in_array($tt,$suggested)&&!in_array($tt,$existing)){$suggested[]=$tt;$reasons[$tt]='trending';if(count($suggested)>=8) break;}
    }
}

// Remove already used
$suggested=array_values(array_diff($suggested,$existing));

// Score and sort
$scored=[];
foreach($suggested as $tag){
    $score=1;
    if(isset($reasons[$tag])&&$reasons[$tag]!=='trending') $score+=3;
    if(mb_strlen($tag)<=10) $score+=1;
    $scored[]=['tag'=>$tag,'score'=>$score,'reason'=>$reasons[$tag]??'suggested'];
}
usort($scored,function($a,$b){return $b['score']-$a['score'];});

echo json_encode(['success'=>true,'data'=>['hashtags'=>array_column(array_slice($scored,0,8),'tag'),'detailed'=>array_slice($scored,0,8),'existing'=>$existing,'total_suggested'=>count($scored)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
