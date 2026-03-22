<?php
// ShipperShop API v2 — Post Auto-Tagging
// Automatically suggest tags/hashtags based on post content keywords
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$KEYWORDS=[
    'ghtk'=>['ghtk','giao hang tiet kiem'],
    'ghn'=>['ghn','giao hang nhanh'],
    'jt'=>['j&t','jnt','j and t'],
    'viettelpost'=>['viettel post','viettel','vtp'],
    'spx'=>['shopee express','spx','shopee'],
    'grab'=>['grab','grabexpress'],
    'be'=>['be','ahamove'],
    'ninjavan'=>['ninja van','ninjavan'],
    'best'=>['best express','best'],
    'giaohang'=>['giao hang','ship hang','don hang','van chuyen'],
    'tuyendung'=>['tuyen dung','can nguoi','tim shipper','tuyen shipper'],
    'hoigiadinh'=>['hoi gia dinh','dia chi','nha o dau','cho nao'],
    'congdong'=>['cong dong','shipper','anh em','ho tro'],
    'kinhnghiem'=>['kinh nghiem','meo','tip','chia se','huong dan'],
    'khieunai'=>['khieu nai','phan nan','toi te','chan','buc xuc'],
    'tintuc'=>['tin tuc','cap nhat','moi','thong bao'],
    'cuahang'=>['cua hang','mua ban','san pham','gia'],
    'thitruong'=>['thi truong','gia xang','phi ship','cuoc ship'],
];

try {
    $text=trim($_GET['text']??'');
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $input=json_decode(file_get_contents('php://input'),true);
        $text=trim($input['text']??$text);
    }
    if(!$text||mb_strlen($text)<5){echo json_encode(['success'=>true,'data'=>['tags'=>[]]]);exit;}

    $lower=mb_strtolower($text);
    $matched=[];$scores=[];
    foreach($KEYWORDS as $tag=>$keywords){
        foreach($keywords as $kw){
            if(mb_strpos($lower,$kw)!==false){
                if(!isset($scores[$tag])) $scores[$tag]=0;
                $scores[$tag]++;
                $matched[$tag]=true;
            }
        }
    }
    arsort($scores);
    $tags=array_keys(array_slice($scores,0,5,true));

    // Also extract existing hashtags
    preg_match_all('/#([a-zA-Z0-9_\x{00C0}-\x{024F}]+)/u',$text,$m);
    $existing=$m[1]??[];

    echo json_encode(['success'=>true,'data'=>['suggested_tags'=>$tags,'existing_hashtags'=>$existing,'keyword_matches'=>count($matched)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
