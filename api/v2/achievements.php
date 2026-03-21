<?php
// ShipperShop API v2 — User Achievements & Milestones
// Track and celebrate user milestones (first post, 100 likes, etc.)
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

function ac_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$MILESTONES=[
    // Posts
    ['id'=>'post_1','cat'=>'content','name'=>'Bài đầu tiên','icon'=>'📝','desc'=>'Đăng bài viết đầu tiên','metric'=>'posts','target'=>1,'xp'=>10],
    ['id'=>'post_10','cat'=>'content','name'=>'Tác giả','icon'=>'✍️','desc'=>'Đăng 10 bài viết','metric'=>'posts','target'=>10,'xp'=>30],
    ['id'=>'post_50','cat'=>'content','name'=>'Blogger','icon'=>'📰','desc'=>'Đăng 50 bài viết','metric'=>'posts','target'=>50,'xp'=>100],
    ['id'=>'post_200','cat'=>'content','name'=>'Nhà báo','icon'=>'🗞️','desc'=>'Đăng 200 bài viết','metric'=>'posts','target'=>200,'xp'=>300],
    // Deliveries
    ['id'=>'del_1','cat'=>'delivery','name'=>'Đơn đầu tiên','icon'=>'📦','desc'=>'Giao đơn đầu tiên','metric'=>'deliveries','target'=>1,'xp'=>10],
    ['id'=>'del_50','cat'=>'delivery','name'=>'Shipper Pro','icon'=>'🚚','desc'=>'Giao 50 đơn','metric'=>'deliveries','target'=>50,'xp'=>100],
    ['id'=>'del_200','cat'=>'delivery','name'=>'Shipper Master','icon'=>'🏆','desc'=>'Giao 200 đơn','metric'=>'deliveries','target'=>200,'xp'=>500],
    ['id'=>'del_1000','cat'=>'delivery','name'=>'Huyền thoại','icon'=>'👑','desc'=>'Giao 1000 đơn','metric'=>'deliveries','target'=>1000,'xp'=>2000],
    // Social
    ['id'=>'follower_10','cat'=>'social','name'=>'Nổi tiếng','icon'=>'📢','desc'=>'10 người theo dõi','metric'=>'followers','target'=>10,'xp'=>20],
    ['id'=>'follower_100','cat'=>'social','name'=>'Influencer','icon'=>'🌟','desc'=>'100 người theo dõi','metric'=>'followers','target'=>100,'xp'=>200],
    ['id'=>'like_100','cat'=>'engagement','name'=>'Được yêu thích','icon'=>'❤️','desc'=>'Nhận 100 lượt thích','metric'=>'likes','target'=>100,'xp'=>50],
    ['id'=>'like_1000','cat'=>'engagement','name'=>'Siêu sao','icon'=>'💖','desc'=>'Nhận 1000 lượt thích','metric'=>'likes','target'=>1000,'xp'=>500],
    ['id'=>'comment_50','cat'=>'engagement','name'=>'Hay nói','icon'=>'💬','desc'=>'Viết 50 ghi chú','metric'=>'comments','target'=>50,'xp'=>50],
    // Streaks
    ['id'=>'streak_7','cat'=>'dedication','name'=>'7 ngày','icon'=>'🔥','desc'=>'Hoạt động 7 ngày liền','metric'=>'streak','target'=>7,'xp'=>30],
    ['id'=>'streak_30','cat'=>'dedication','name'=>'30 ngày','icon'=>'💪','desc'=>'Hoạt động 30 ngày liền','metric'=>'streak','target'=>30,'xp'=>150],
    ['id'=>'streak_100','cat'=>'dedication','name'=>'100 ngày','icon'=>'🦾','desc'=>'Hoạt động 100 ngày liền','metric'=>'streak','target'=>100,'xp'=>1000],
    // Special
    ['id'=>'verified','cat'=>'special','name'=>'Xác minh','icon'=>'✅','desc'=>'Xác minh tài khoản','metric'=>'verified','target'=>1,'xp'=>50],
    ['id'=>'group_create','cat'=>'special','name'=>'Trưởng nhóm','icon'=>'👥','desc'=>'Tạo một nhóm','metric'=>'groups_created','target'=>1,'xp'=>30],
    ['id'=>'referral_5','cat'=>'special','name'=>'Đại sứ','icon'=>'🤝','desc'=>'Giới thiệu 5 người','metric'=>'referrals','target'=>5,'xp'=>100],
];

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) ac_ok('OK',['achievements'=>[],'stats'=>[]]);

$action=$_GET['action']??'';

$data=cache_remember('achievements_'.$userId, function() use($d,$userId,$MILESTONES) {
    $u=$d->fetchOne("SELECT total_posts,total_success,is_verified,created_at FROM users WHERE id=?",[$userId]);
    if(!$u) return ['achievements'=>[],'stats'=>[],'total_xp'=>0];

    $likes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$userId])['s']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$userId])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $streak=intval($d->fetchOne("SELECT GREATEST(COALESCE(current_streak,0),COALESCE(longest_streak,0)) as s FROM user_streaks WHERE user_id=?",[$userId])['s']??0);
    $groupsCreated=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=? AND role='admin'",[$userId])['c']);
    $referrals=intval($d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE referrer_id=?",[$userId])['c']??0);

    $metrics=['posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'followers'=>$followers,'likes'=>$likes,'comments'=>$comments,'streak'=>$streak,'verified'=>intval($u['is_verified']),'groups_created'=>$groupsCreated,'referrals'=>$referrals];

    $earned=[];$upcoming=[];$totalXp=0;
    foreach($MILESTONES as $m){
        $current=$metrics[$m['metric']]??0;
        $done=$current>=$m['target'];
        $item=$m;
        $item['current']=$current;
        $item['progress']=min(100,$m['target']>0?round($current/$m['target']*100):0);
        $item['earned']=$done;
        if($done){$earned[]=$item;$totalXp+=$m['xp'];}
        else{$upcoming[]=$item;}
    }

    // Sort upcoming by progress desc
    usort($upcoming,function($a,$b){return $b['progress']-$a['progress'];});

    return ['earned'=>$earned,'upcoming'=>array_slice($upcoming,0,8),'stats'=>$metrics,'total_earned'=>count($earned),'total_available'=>count($MILESTONES),'total_xp'=>$totalXp];
}, 300);

// Leaderboard by achievements
if($action==='leaderboard'){
    $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.total_posts,u.total_success,u.is_verified FROM users u WHERE u.`status`='active' ORDER BY (u.total_posts+u.total_success*2) DESC LIMIT 20");
    ac_ok('OK',$users);
}

ac_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
