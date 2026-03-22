<?php
// ShipperShop API v2 — Weekly Challenge
// Gamified weekly challenges: post X times, get Y likes, etc.
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

$CHALLENGES=[
    ['id'=>'post_5','name'=>'Cay but cham chi','desc'=>'Dang 5 bai viet trong tuan','icon'=>'📝','type'=>'posts','target'=>5,'xp'=>50],
    ['id'=>'like_20','name'=>'Nguoi ung ho','desc'=>'Thich 20 bai viet','icon'=>'❤️','type'=>'likes_given','target'=>20,'xp'=>30],
    ['id'=>'comment_10','name'=>'Nguoi ghi chu','desc'=>'Binh luan 10 lan','icon'=>'💬','type'=>'comments','target'=>10,'xp'=>40],
    ['id'=>'streak_7','name'=>'Khong nghi','desc'=>'Hoat dong 7 ngay lien tiep','icon'=>'🔥','type'=>'streak','target'=>7,'xp'=>100],
    ['id'=>'follower_5','name'=>'Nguoi anh huong','desc'=>'Co them 5 nguoi theo doi','icon'=>'👥','type'=>'new_followers','target'=>5,'xp'=>60],
    ['id'=>'share_3','name'=>'Nguoi chia se','desc'=>'Chia se 3 bai viet','icon'=>'🔄','type'=>'shares','target'=>3,'xp'=>25],
];

function wc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$weekStart=date('Y-m-d',strtotime('monday this week'));

$challenges=[];
foreach($CHALLENGES as $ch){
    $current=0;
    switch($ch['type']){
        case 'posts': $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= ?",[$uid,$weekStart])['c']);break;
        case 'likes_given': $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id=? AND created_at >= ?",[$uid,$weekStart])['c']);break;
        case 'comments': $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at >= ?",[$uid,$weekStart])['c']);break;
        case 'streak': $current=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$uid])['current_streak']??0);break;
        case 'new_followers': $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at >= ?",[$uid,$weekStart])['c']);break;
        case 'shares': $current=intval($d->fetchOne("SELECT COALESCE(SUM(shares_count),0) as s FROM posts WHERE user_id=? AND created_at >= ?",[$uid,$weekStart])['s']);break;
    }
    $ch['current']=$current;
    $ch['progress']=min(100,round($current/$ch['target']*100));
    $ch['completed']=$current>=$ch['target'];
    $challenges[]=$ch;
}

$completed=count(array_filter($challenges,function($c){return $c['completed'];}));
$totalXp=array_sum(array_map(function($c){return $c['completed']?$c['xp']:0;},$challenges));

wc_ok('OK',['challenges'=>$challenges,'week_start'=>$weekStart,'completed'=>$completed,'total'=>count($challenges),'xp_earned'=>$totalXp,'week_ends'=>date('Y-m-d',strtotime('next sunday'))]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
