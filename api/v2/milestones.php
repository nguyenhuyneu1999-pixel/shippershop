<?php
// ShipperShop API v2 — User Milestones
// Track and celebrate user milestones (first post, 100 likes, 1000 deliveries, etc.)
session_start();
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

function ms_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$MILESTONES=[
    ['id'=>'first_post','name'=>'Bai viet dau tien','icon'=>'📝','desc'=>'Dang bai viet dau tien','check'=>'posts','threshold'=>1],
    ['id'=>'10_posts','name'=>'10 bai viet','icon'=>'✍️','desc'=>'Dang 10 bai viet','check'=>'posts','threshold'=>10],
    ['id'=>'50_posts','name'=>'50 bai viet','icon'=>'🏅','desc'=>'Dang 50 bai viet','check'=>'posts','threshold'=>50],
    ['id'=>'100_posts','name'=>'Tram bai','icon'=>'💯','desc'=>'Dang 100 bai viet','check'=>'posts','threshold'=>100],
    ['id'=>'first_like','name'=>'Duoc thich','icon'=>'❤️','desc'=>'Nhan like dau tien','check'=>'likes','threshold'=>1],
    ['id'=>'100_likes','name'=>'100 luot thich','icon'=>'🔥','desc'=>'Nhan 100 luot thich','check'=>'likes','threshold'=>100],
    ['id'=>'500_likes','name'=>'500 luot thich','icon'=>'⭐','desc'=>'Nhan 500 luot thich','check'=>'likes','threshold'=>500],
    ['id'=>'first_follower','name'=>'Nguoi theo doi dau tien','icon'=>'👤','desc'=>'Co nguoi theo doi dau tien','check'=>'followers','threshold'=>1],
    ['id'=>'50_followers','name'=>'50 nguoi theo doi','icon'=>'👥','desc'=>'Co 50 nguoi theo doi','check'=>'followers','threshold'=>50],
    ['id'=>'100_deliveries','name'=>'100 don giao','icon'=>'📦','desc'=>'Giao 100 don thanh cong','check'=>'deliveries','threshold'=>100],
    ['id'=>'500_deliveries','name'=>'500 don giao','icon'=>'🚀','desc'=>'Giao 500 don thanh cong','check'=>'deliveries','threshold'=>500],
    ['id'=>'1000_deliveries','name'=>'1000 don giao','icon'=>'🏆','desc'=>'Giao 1000 don thanh cong','check'=>'deliveries','threshold'=>1000],
    ['id'=>'7_streak','name'=>'Streak 7 ngay','icon'=>'🔥','desc'=>'Hoat dong 7 ngay lien tiep','check'=>'streak','threshold'=>7],
    ['id'=>'30_streak','name'=>'Streak 30 ngay','icon'=>'💪','desc'=>'Hoat dong 30 ngay lien tiep','check'=>'streak','threshold'=>30],
    ['id'=>'join_group','name'=>'Tham gia nhom','icon'=>'👥','desc'=>'Tham gia nhom dau tien','check'=>'groups','threshold'=>1],
];

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) ms_ok('OK',['milestones'=>$MILESTONES,'earned'=>[],'progress'=>[]]);

$data=cache_remember('milestones_'.$userId, function() use($d,$userId,$MILESTONES) {
    $u=$d->fetchOne("SELECT total_posts,total_success FROM users WHERE id=?",[$userId]);
    $posts=intval($u['total_posts']??0);
    $deliveries=intval($u['total_success']??0);
    $likes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$userId])['s']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$userId])['current_streak']??0);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$userId])['c']);

    $values=['posts'=>$posts,'likes'=>$likes,'followers'=>$followers,'deliveries'=>$deliveries,'streak'=>$streak,'groups'=>$groups];
    $earned=[];$progress=[];
    foreach($MILESTONES as $m){
        $current=$values[$m['check']]??0;
        $pct=min(100,round($current/$m['threshold']*100));
        $done=$current>=$m['threshold'];
        $m['current']=$current;$m['progress']=$pct;$m['earned']=$done;
        if($done) $earned[]=$m;
        $progress[]=$m;
    }

    return ['earned'=>$earned,'progress'=>$progress,'total_earned'=>count($earned),'total'=>count($MILESTONES),'values'=>$values];
}, 300);

ms_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
