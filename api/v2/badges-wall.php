<?php
// ShipperShop API v2 — Badges Wall
// All available badges + user progress toward earning them
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

$ALL_BADGES=[
    ['id'=>'first_post','name'=>'Bai viet dau tien','icon'=>'📝','desc'=>'Dang bai viet dau tien','condition'=>'posts>=1'],
    ['id'=>'popular_10','name'=>'Duoc yeu thich','icon'=>'❤️','desc'=>'Nhan 10 luot thich','condition'=>'total_likes>=10'],
    ['id'=>'popular_100','name'=>'Ngoi sao','icon'=>'⭐','desc'=>'Nhan 100 luot thich','condition'=>'total_likes>=100'],
    ['id'=>'social_butterfly','name'=>'Thuoc da xa hoi','icon'=>'🦋','desc'=>'Theo doi 10 nguoi','condition'=>'following>=10'],
    ['id'=>'influencer','name'=>'Nguoi anh huong','icon'=>'👑','desc'=>'Co 50 nguoi theo doi','condition'=>'followers>=50'],
    ['id'=>'commenter','name'=>'Binh luan vien','icon'=>'💬','desc'=>'Viet 50 binh luan','condition'=>'comments>=50'],
    ['id'=>'streak_7','name'=>'7 ngay lien tiep','icon'=>'🔥','desc'=>'Dang nhap 7 ngay lien tuc','condition'=>'streak>=7'],
    ['id'=>'streak_30','name'=>'30 ngay lien tiep','icon'=>'🏆','desc'=>'Dang nhap 30 ngay lien tuc','condition'=>'streak>=30'],
    ['id'=>'group_leader','name'=>'Truong nhom','icon'=>'👥','desc'=>'Tao 1 nhom','condition'=>'groups_created>=1'],
    ['id'=>'shipper_pro','name'=>'Shipper Pro','icon'=>'📦','desc'=>'Giao 100 don thanh cong','condition'=>'deliveries>=100'],
    ['id'=>'shipper_master','name'=>'Shipper Master','icon'=>'🚀','desc'=>'Giao 500 don thanh cong','condition'=>'deliveries>=500'],
    ['id'=>'verified','name'=>'Xac minh','icon'=>'✅','desc'=>'Tai khoan da xac minh','condition'=>'verified'],
    ['id'=>'early_bird','name'=>'Thanh vien som','icon'=>'🐦','desc'=>'Dang ky trong 100 nguoi dau','condition'=>'user_id<=100'],
    ['id'=>'content_king','name'=>'Vua noi dung','icon'=>'📖','desc'=>'Dang 50 bai viet','condition'=>'posts>=50'],
    ['id'=>'helper','name'=>'Nguoi giup do','icon'=>'🤝','desc'=>'Tra loi 100 binh luan','condition'=>'comments>=100'],
];

function bw_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=optional_auth();
$targetUid=intval($_GET['user_id']??0);
if(!$targetUid) $targetUid=$uid;

// Get user stats for progress
$stats=null;
if($targetUid){
    $u=$d->fetchOne("SELECT total_posts,total_success,is_verified,id FROM users WHERE id=?",[$targetUid]);
    if($u){
        $stats=[
            'posts'=>intval($u['total_posts']),
            'deliveries'=>intval($u['total_success']),
            'verified'=>intval($u['is_verified']??0),
            'user_id'=>intval($u['id']),
            'total_likes'=>intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$targetUid])['s']),
            'followers'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$targetUid])['c']),
            'following'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$targetUid])['c']),
            'comments'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$targetUid])['c']),
            'streak'=>intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$targetUid])['current_streak']??0),
            'groups_created'=>0,
        ];
    }
}

// Earned badges from DB
$earned=$d->fetchAll("SELECT badge_id FROM user_badges WHERE user_id=?",[$targetUid??0]);
$earnedIds=array_column($earned,'badge_id');

// Build badge wall with progress
$badges=[];
foreach($ALL_BADGES as $b){
    $b['earned']=in_array($b['id'],$earnedIds);
    $b['progress']=0;
    if($stats){
        // Calculate progress
        if(preg_match('/(\w+)>=(\d+)/',$b['condition'],$m)){
            $metric=$m[1];$target=intval($m[2]);
            $current=$stats[$metric]??0;
            $b['progress']=min(100,round($current/$target*100));
            $b['current']=$current;
            $b['target']=$target;
        }elseif($b['condition']==='verified'){
            $b['progress']=$stats['verified']?100:0;
        }
    }
    $badges[]=$b;
}

$earnedCount=count(array_filter($badges,function($b){return $b['earned'];}));
bw_ok('OK',['badges'=>$badges,'earned_count'=>$earnedCount,'total_count'=>count($badges),'completion'=>count($badges)>0?round($earnedCount/count($badges)*100):0]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
