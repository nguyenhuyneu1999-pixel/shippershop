<?php
// ShipperShop API v2 — Leaderboard Seasons
// Monthly/weekly competitive leaderboards with rewards
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function ls_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$period=$_GET['period']??'monthly'; // weekly, monthly
$metric=$_GET['metric']??'posts'; // posts, likes, deliveries, xp, comments

// Current season leaderboard
if(!$action||$action==='current'){
    $interval=$period==='weekly'?'7 DAY':'30 DAY';
    $seasonName=$period==='weekly'?('Tuan '.date('W/Y')):('Thang '.date('m/Y'));

    $orderCol='total_posts';$selectExtra='';
    if($metric==='likes') $orderCol='(SELECT COALESCE(SUM(likes_count),0) FROM posts WHERE user_id=u.id AND `status`=\'active\' AND created_at >= DATE_SUB(NOW(), INTERVAL '.$interval.'))';
    elseif($metric==='deliveries') $orderCol='total_success';
    elseif($metric==='xp') $selectExtra=",COALESCE((SELECT SUM(xp) FROM user_xp WHERE user_id=u.id AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)),0) as season_xp";
    elseif($metric==='comments') $selectExtra=",COALESCE((SELECT COUNT(*) FROM comments WHERE user_id=u.id AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)),0) as season_comments";

    if($metric==='posts'){
        $top=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(p.id) as season_score FROM users u LEFT JOIN posts p ON u.id=p.user_id AND p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $interval) WHERE u.`status`='active' GROUP BY u.id HAVING season_score>0 ORDER BY season_score DESC LIMIT 20");
    }elseif($metric==='xp'){
        $top=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COALESCE(SUM(ux.xp),0) as season_score FROM users u LEFT JOIN user_xp ux ON u.id=ux.user_id AND ux.created_at >= DATE_SUB(NOW(), INTERVAL $interval) WHERE u.`status`='active' GROUP BY u.id HAVING season_score>0 ORDER BY season_score DESC LIMIT 20");
    }else{
        $top=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.$orderCol as season_score FROM users u WHERE u.`status`='active' AND u.$orderCol>0 ORDER BY season_score DESC LIMIT 20");
    }

    $rewards=[['rank'=>1,'reward'=>'🥇 Badge Vang + 500 XP','color'=>'#ffd700'],['rank'=>2,'reward'=>'🥈 Badge Bac + 300 XP','color'=>'#c0c0c0'],['rank'=>3,'reward'=>'🥉 Badge Dong + 100 XP','color'=>'#cd7f32']];

    ls_ok('OK',['season'=>$seasonName,'period'=>$period,'metric'=>$metric,'leaderboard'=>$top,'rewards'=>$rewards,'ends_in'=>$period==='weekly'?date('Y-m-d',strtotime('next monday')):date('Y-m-t')]);
}

// Available metrics
if($action==='metrics'){
    ls_ok('OK',['metrics'=>[['id'=>'posts','name'=>'Bai viet','icon'=>'📝'],['id'=>'likes','name'=>'Luot thich','icon'=>'❤️'],['id'=>'deliveries','name'=>'Don giao','icon'=>'📦'],['id'=>'xp','name'=>'XP','icon'=>'⭐'],['id'=>'comments','name'=>'Ghi chu','icon'=>'💬']]]);
}

ls_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
