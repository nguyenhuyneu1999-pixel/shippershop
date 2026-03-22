<?php
// ShipperShop API v2 — User Ratings
// Rate shippers: delivery speed, communication, reliability (1-5 stars)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$CATEGORIES=[
    ['id'=>'speed','name'=>'Toc do giao','icon'=>'⚡'],
    ['id'=>'communication','name'=>'Giao tiep','icon'=>'💬'],
    ['id'=>'reliability','name'=>'Tin cay','icon'=>'🛡️'],
    ['id'=>'attitude','name'=>'Thai do','icon'=>'😊'],
    ['id'=>'packaging','name'=>'Dong goi','icon'=>'📦'],
];

function ur_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// GET: user's ratings
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) ur_ok('OK',['categories'=>$CATEGORIES]);

    $data=cache_remember('user_rating_'.$userId, function() use($d,$userId,$CATEGORIES) {
        $key='ratings_for_'.$userId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $allRatings=$row?json_decode($row['value'],true):[];

        $avgByCategory=[];$totalAvg=0;$count=count($allRatings);
        foreach($CATEGORIES as $cat){
            $catRatings=[];
            foreach($allRatings as $r){if(isset($r['scores'][$cat['id']])) $catRatings[]=intval($r['scores'][$cat['id']]);}
            $avg=count($catRatings)?round(array_sum($catRatings)/count($catRatings),1):0;
            $avgByCategory[$cat['id']]=$avg;
            $totalAvg+=$avg;
        }
        $overall=count($CATEGORIES)?round($totalAvg/count($CATEGORIES),1):0;

        return ['overall'=>$overall,'by_category'=>$avgByCategory,'total_reviews'=>$count,'categories'=>$CATEGORIES,'recent'=>array_slice($allRatings,0,5)];
    }, 300);

    ur_ok('OK',$data);
}

// POST: rate a user
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $targetId=intval($input['user_id']??0);
    $scores=$input['scores']??[];
    $comment=trim($input['comment']??'');
    if(!$targetId||$targetId===$uid) ur_ok('Khong the tu danh gia');
    if(!$scores) ur_ok('Chua chon diem');

    // Validate scores 1-5
    foreach($scores as $k=>$v){$scores[$k]=max(1,min(5,intval($v)));}

    $key='ratings_for_'.$targetId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $allRatings=$row?json_decode($row['value'],true):[];

    // Remove existing rating by this user
    $allRatings=array_values(array_filter($allRatings,function($r) use($uid){return intval($r['by']??0)!==$uid;}));

    // Add new
    array_unshift($allRatings,['by'=>$uid,'scores'=>$scores,'comment'=>$comment,'created_at'=>date('c')]);
    if(count($allRatings)>200) $allRatings=array_slice($allRatings,0,200);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($allRatings),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($allRatings)]);

    ur_ok('Da danh gia!',['total_reviews'=>count($allRatings)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
