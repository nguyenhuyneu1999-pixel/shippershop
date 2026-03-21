<?php
// ShipperShop API v2 — Achievement Milestones
// Track user milestones (first 10 posts, 100 likes, 7-day streak, etc.)
// Returns unlocked milestones + next goals
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

function ml_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$MILESTONES=[
    ['id'=>'post_1','cat'=>'content','icon'=>'📝','name'=>'Bước đầu tiên','desc'=>'Đăng bài đầu tiên','metric'=>'posts','target'=>1,'xp'=>10],
    ['id'=>'post_10','cat'=>'content','icon'=>'✍️','name'=>'Người viết chăm chỉ','desc'=>'Đăng 10 bài viết','metric'=>'posts','target'=>10,'xp'=>50],
    ['id'=>'post_50','cat'=>'content','icon'=>'📰','name'=>'Blogger','desc'=>'Đăng 50 bài viết','metric'=>'posts','target'=>50,'xp'=>200],
    ['id'=>'post_200','cat'=>'content','icon'=>'📚','name'=>'Tác giả','desc'=>'Đăng 200 bài viết','metric'=>'posts','target'=>200,'xp'=>500],
    ['id'=>'like_10','cat'=>'engage','icon'=>'👍','name'=>'Được thích','desc'=>'Nhận 10 lượt thích','metric'=>'likes_received','target'=>10,'xp'=>20],
    ['id'=>'like_100','cat'=>'engage','icon'=>'❤️','name'=>'Người được yêu','desc'=>'Nhận 100 lượt thích','metric'=>'likes_received','target'=>100,'xp'=>100],
    ['id'=>'like_500','cat'=>'engage','icon'=>'🌟','name'=>'Ngôi sao sáng','desc'=>'Nhận 500 lượt thích','metric'=>'likes_received','target'=>500,'xp'=>300],
    ['id'=>'ship_10','cat'=>'delivery','icon'=>'📦','name'=>'10 đơn đầu','desc'=>'Giao 10 đơn thành công','metric'=>'deliveries','target'=>10,'xp'=>30],
    ['id'=>'ship_50','cat'=>'delivery','icon'=>'🚚','name'=>'50 đơn','desc'=>'Giao 50 đơn thành công','metric'=>'deliveries','target'=>50,'xp'=>150],
    ['id'=>'ship_200','cat'=>'delivery','icon'=>'🏆','name'=>'200 đơn','desc'=>'Giao 200 đơn thành công','metric'=>'deliveries','target'=>200,'xp'=>500],
    ['id'=>'ship_1000','cat'=>'delivery','icon'=>'👑','name'=>'Vua giao hàng','desc'=>'Giao 1000 đơn thành công','metric'=>'deliveries','target'=>1000,'xp'=>2000],
    ['id'=>'follow_10','cat'=>'social','icon'=>'👥','name'=>'Có bạn','desc'=>'10 người theo dõi','metric'=>'followers','target'=>10,'xp'=>30],
    ['id'=>'follow_100','cat'=>'social','icon'=>'📢','name'=>'Influencer','desc'=>'100 người theo dõi','metric'=>'followers','target'=>100,'xp'=>200],
    ['id'=>'streak_7','cat'=>'streak','icon'=>'🔥','name'=>'7 ngày liên tục','desc'=>'Hoạt động 7 ngày liền','metric'=>'streak','target'=>7,'xp'=>70],
    ['id'=>'streak_30','cat'=>'streak','icon'=>'💪','name'=>'30 ngày liên tục','desc'=>'Hoạt động 30 ngày liền','metric'=>'streak','target'=>30,'xp'=>300],
    ['id'=>'streak_100','cat'=>'streak','icon'=>'🏅','name'=>'100 ngày liên tục','desc'=>'Hoạt động 100 ngày liền','metric'=>'streak','target'=>100,'xp'=>1000],
    ['id'=>'comment_20','cat'=>'engage','icon'=>'💬','name'=>'Người ghi chú','desc'=>'Viết 20 bình luận','metric'=>'comments','target'=>20,'xp'=>40],
    ['id'=>'comment_100','cat'=>'engage','icon'=>'🗣️','name'=>'Diễn giả','desc'=>'Viết 100 bình luận','metric'=>'comments','target'=>100,'xp'=>200],
    ['id'=>'days_30','cat'=>'loyalty','icon'=>'📅','name'=>'1 tháng','desc'=>'Thành viên 30 ngày','metric'=>'days','target'=>30,'xp'=>30],
    ['id'=>'days_365','cat'=>'loyalty','icon'=>'🎂','name'=>'1 năm','desc'=>'Thành viên 365 ngày','metric'=>'days','target'=>365,'xp'=>500],
];

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) ml_ok('OK',['unlocked'=>[],'next'=>[]]);

$action=$_GET['action']??'';

$data=cache_remember('milestones_'.$userId, function() use($d,$userId,$MILESTONES) {
    $u=$d->fetchOne("SELECT total_posts,total_success,is_verified,created_at FROM users WHERE id=?",[$userId]);
    if(!$u) return ['unlocked'=>[],'next'=>[],'total_xp'=>0];

    $likes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$userId])['s']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$userId])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $streak=intval($d->fetchOne("SELECT GREATEST(COALESCE(current_streak,0),COALESCE(longest_streak,0)) as s FROM user_streaks WHERE user_id=?",[$userId])['s']??0);
    $days=max(1,floor((time()-strtotime($u['created_at']))/86400));

    $metrics=['posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'likes_received'=>$likes,'comments'=>$comments,'followers'=>$followers,'streak'=>$streak,'days'=>$days];

    $unlocked=[];$next=[];$totalXp=0;
    foreach($MILESTONES as $m){
        $current=$metrics[$m['metric']]??0;
        $pct=min(100,round($current/$m['target']*100));
        $done=$current>=$m['target'];
        $entry=array_merge($m,['current'=>$current,'progress'=>$pct,'unlocked'=>$done]);
        if($done){$unlocked[]=$entry;$totalXp+=$m['xp'];}
        else{$next[]=$entry;}
    }

    // Sort next by progress desc (closest to unlock)
    usort($next,function($a,$b){return $b['progress']-$a['progress'];});

    return ['unlocked'=>$unlocked,'next'=>array_slice($next,0,5),'total_xp'=>$totalXp,'metrics'=>$metrics,'total_milestones'=>count($MILESTONES)];
}, 300);

ml_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
