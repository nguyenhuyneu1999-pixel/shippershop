<?php
// ShipperShop API v2 — Badges Wall
// Public badges leaderboard + all available badges catalog
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function bw_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$BADGES=[
    ['id'=>'newcomer','name'=>'Tan binh','icon'=>'🆕','desc'=>'Dang ky tai khoan','rarity'=>'common'],
    ['id'=>'first_post','name'=>'Cay but','icon'=>'✍️','desc'=>'Dang bai viet dau tien','rarity'=>'common'],
    ['id'=>'social','name'=>'Hoa dong','icon'=>'🤝','desc'=>'Theo doi 10 nguoi','rarity'=>'common'],
    ['id'=>'commenter','name'=>'Binh luan vien','icon'=>'💬','desc'=>'Viet 50 binh luan','rarity'=>'uncommon'],
    ['id'=>'popular','name'=>'Noi tieng','icon'=>'⭐','desc'=>'Dat 100 luot thich','rarity'=>'uncommon'],
    ['id'=>'consistent','name'=>'Kien tri','icon'=>'🔥','desc'=>'Streak 7 ngay lien tiep','rarity'=>'uncommon'],
    ['id'=>'pro_shipper','name'=>'Pro Shipper','icon'=>'🏅','desc'=>'Giao 500 don thanh cong','rarity'=>'rare'],
    ['id'=>'influencer','name'=>'Nguoi anh huong','icon'=>'👑','desc'=>'Co 100 nguoi theo doi','rarity'=>'rare'],
    ['id'=>'content_king','name'=>'Vua noi dung','icon'=>'📝','desc'=>'Dang 100 bai viet','rarity'=>'rare'],
    ['id'=>'veteran','name'=>'Ky cuu','icon'=>'🎖️','desc'=>'Tham gia tren 1 nam','rarity'=>'epic'],
    ['id'=>'legend','name'=>'Huyen thoai','icon'=>'🏆','desc'=>'Giao 1000 don + 100 bai','rarity'=>'epic'],
    ['id'=>'og','name'=>'OG Member','icon'=>'💎','desc'=>'Top 100 thanh vien dau tien','rarity'=>'legendary'],
];

try {

// Catalog
if(!$action||$action==='catalog'){
    bw_ok('OK',['badges'=>$BADGES,'total'=>count($BADGES),'rarities'=>['common'=>4,'uncommon'=>3,'rare'=>3,'epic'=>2,'legendary'=>1]]);
}

// Leaderboard (most badges earned)
if($action==='leaderboard'){
    $top=cache_remember('badges_leaderboard', function() use($d) {
        return $d->fetchAll("SELECT ub.user_id,u.fullname,u.avatar,u.shipping_company,COUNT(ub.id) as badge_count FROM user_badges ub JOIN users u ON ub.user_id=u.id WHERE u.`status`='active' GROUP BY ub.user_id ORDER BY badge_count DESC LIMIT 20");
    }, 600);
    bw_ok('OK',$top);
}

// User's badges
if($action==='user'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) bw_ok('OK',['badges'=>[]]);
    $earned=$d->fetchAll("SELECT badge_id,created_at FROM user_badges WHERE user_id=? ORDER BY created_at DESC",[$userId]);
    $earnedIds=array_column($earned,'badge_id');
    $result=[];
    foreach($BADGES as $b){
        $b['earned']=in_array($b['id'],$earnedIds);
        if($b['earned']){
            foreach($earned as $e){if($e['badge_id']===$b['id']){$b['earned_at']=$e['created_at'];break;}}
        }
        $result[]=$b;
    }
    bw_ok('OK',['badges'=>$result,'earned_count'=>count($earnedIds),'total'=>count($BADGES)]);
}

bw_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
