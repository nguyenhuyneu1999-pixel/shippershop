<?php
// ShipperShop API v2 — Badge Showcase
// User's earned badges collection, progress to next badges
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

function bs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

// All possible badges
$ALL_BADGES=[
    ['id'=>'first_post','name'=>'Bài đầu tiên','icon'=>'📝','desc'=>'Đăng bài viết đầu tiên','check'=>'posts>=1'],
    ['id'=>'popular_10','name'=>'Được yêu thích','icon'=>'❤️','desc'=>'Nhận 10 lượt thích','check'=>'likes>=10'],
    ['id'=>'popular_100','name'=>'Ngôi sao','icon'=>'⭐','desc'=>'Nhận 100 lượt thích','check'=>'likes>=100'],
    ['id'=>'commenter','name'=>'Ghi chú viên','icon'=>'💬','desc'=>'Viết 20 bình luận','check'=>'comments>=20'],
    ['id'=>'delivery_10','name'=>'Shipper mới','icon'=>'📦','desc'=>'10 đơn giao thành công','check'=>'deliveries>=10'],
    ['id'=>'delivery_50','name'=>'Shipper chuyên nghiệp','icon'=>'🚚','desc'=>'50 đơn giao thành công','check'=>'deliveries>=50'],
    ['id'=>'delivery_200','name'=>'Shipper huyền thoại','icon'=>'🏆','desc'=>'200 đơn giao thành công','check'=>'deliveries>=200'],
    ['id'=>'streak_7','name'=>'7 ngày liên tục','icon'=>'🔥','desc'=>'Hoạt động 7 ngày liền','check'=>'streak>=7'],
    ['id'=>'streak_30','name'=>'30 ngày liên tục','icon'=>'💪','desc'=>'Hoạt động 30 ngày liền','check'=>'streak>=30'],
    ['id'=>'verified','name'=>'Đã xác minh','icon'=>'✅','desc'=>'Tài khoản được xác minh','check'=>'verified'],
    ['id'=>'group_leader','name'=>'Trưởng nhóm','icon'=>'👥','desc'=>'Quản lý một nhóm','check'=>'group_admin'],
    ['id'=>'follower_50','name'=>'Người có ảnh hưởng','icon'=>'📢','desc'=>'50 người theo dõi','check'=>'followers>=50'],
    ['id'=>'old_timer','name'=>'Thành viên kỳ cựu','icon'=>'🎖️','desc'=>'Tham gia trên 180 ngày','check'=>'days>=180'],
    ['id'=>'helper','name'=>'Người giúp đỡ','icon'=>'🤝','desc'=>'50 ghi chú hữu ích','check'=>'comments>=50'],
    ['id'=>'pro_sub','name'=>'Thành viên PRO','icon'=>'💎','desc'=>'Đăng ký gói PRO+','check'=>'subscription>=2'],
];

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) bs_ok('OK',['earned'=>[],'available'=>$ALL_BADGES]);

$data=cache_remember('badges_'.$userId, function() use($d,$userId,$ALL_BADGES) {
    $u=$d->fetchOne("SELECT total_posts,total_success,is_verified,created_at FROM users WHERE id=?",[$userId]);
    if(!$u) return ['earned'=>[],'available'=>$ALL_BADGES,'stats'=>[]];

    $likes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$userId])['s']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$userId])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$userId])['current_streak']??0);
    $longestStreak=intval($d->fetchOne("SELECT longest_streak FROM user_streaks WHERE user_id=?",[$userId])['longest_streak']??0);
    $groupAdmin=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=? AND role='admin'",[$userId])['c']);
    $sub=$d->fetchOne("SELECT plan_id FROM user_subscriptions WHERE user_id=? AND `status`='active'",[$userId]);
    $subPlan=intval($sub['plan_id']??0);
    $days=max(1,floor((time()-strtotime($u['created_at']))/86400));

    $stats=['posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'likes'=>$likes,'comments'=>$comments,'followers'=>$followers,'streak'=>max($streak,$longestStreak),'days'=>$days,'verified'=>intval($u['is_verified']),'group_admin'=>$groupAdmin>0?1:0,'subscription'=>$subPlan];

    $earned=[];$available=[];
    foreach($ALL_BADGES as $b){
        $check=$b['check'];$met=false;
        if(preg_match('/(\w+)>=(\d+)/',$check,$m)){
            $met=($stats[$m[1]]??0)>=intval($m[2]);
        }elseif($check==='verified'){$met=!!$stats['verified'];}
        elseif($check==='group_admin'){$met=!!$stats['group_admin'];}

        $badge=$b;
        $badge['earned']=$met;
        if(preg_match('/(\w+)>=(\d+)/',$check,$m)){
            $current=$stats[$m[1]]??0;$target=intval($m[2]);
            $badge['progress']=min(100,round($current/$target*100));
            $badge['current']=$current;$badge['target']=$target;
        }
        if($met) $earned[]=$badge; else $available[]=$badge;
    }

    return ['earned'=>$earned,'available'=>$available,'stats'=>$stats,'total_earned'=>count($earned),'total_available'=>count($ALL_BADGES)];
}, 300);

bs_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
