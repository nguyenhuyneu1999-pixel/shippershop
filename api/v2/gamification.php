<?php
/**
 * ShipperShop API v2 — Gamification (XP, badges, streaks, leaderboard, check-in, achievements)
 */
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

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// XP per action
$XP_MAP = ['checkin'=>5,'post'=>10,'comment'=>3,'like_received'=>1,'follow_received'=>2,'group_join'=>5,'first_post'=>20,'streak_7'=>50,'streak_30'=>200];

// Level thresholds
function getLevel($xp){
    $levels = [[0,'Tân binh','🌱'],[50,'Shipper mới','📦'],[200,'Shipper quen','🚚'],[500,'Shipper Pro','⭐'],[1000,'Shipper VIP','👑'],[2500,'Shipper Elite','💎'],[5000,'Shipper Legend','🏆'],[10000,'Shipper Master','🔥']];
    $current = $levels[0];
    $next = isset($levels[1]) ? $levels[1] : null;
    for($i=0;$i<count($levels);$i++){
        if($xp >= $levels[$i][0]){
            $current = $levels[$i];
            $next = isset($levels[$i+1]) ? $levels[$i+1] : null;
        }
    }
    return ['level'=>$current[1],'icon'=>$current[2],'min_xp'=>$current[0],'next_level'=>$next?$next[1]:null,'next_xp'=>$next?$next[0]:null,'progress'=>$next?round(($xp-$current[0])/($next[0]-$current[0])*100):100];
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Profile (XP + level + streak + badges)
    if($action==='profile'){
        $tid=intval($_GET['user_id']??($uid??0));
        if(!$tid) fail('Missing user');
        $xpRow=$d->fetchOne("SELECT COALESCE(SUM(xp),0) as total FROM user_xp WHERE user_id=?",[$tid]);
        $totalXp=intval($xpRow['total']);
        $level=getLevel($totalXp);

        // Streak
        $streak=$d->fetchOne("SELECT current_streak,longest_streak,last_checkin FROM user_streaks WHERE user_id=?",[$tid]);
        $streakData=['current'=>intval($streak['current_streak']??0),'longest'=>intval($streak['longest_streak']??0),'last_checkin'=>$streak['last_checkin']??null];

        // Badges
        try{$badges=$d->fetchAll("SELECT badge_type,badge_name,badge_icon,earned_at FROM user_badges WHERE user_id=? ORDER BY earned_at DESC",[$tid]);}catch(\Throwable $e){$badges=[];}

        // Recent XP
        $recent=$d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$tid]);

        // Today stats
        $today=date('Y-m-d');
        $todayXp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=? AND DATE(created_at)=?",[$tid,$today])['s']);
        $checkedIn=!!$d->fetchOne("SELECT id FROM user_xp WHERE user_id=? AND action='checkin' AND DATE(created_at)=?",[$tid,$today]);

        ok('OK',[
            'total_xp'=>$totalXp,
            'level'=>$level,
            'streak'=>$streakData,
            'badges'=>$badges,
            'recent'=>$recent,
            'today_xp'=>$todayXp,
            'checked_in'=>$checkedIn
        ]);
    }

    // Leaderboard
    if($action==='leaders'||$action==='leaderboard'){
        $period=$_GET['period']??'all'; // all, month, week
        $limit=min(intval($_GET['limit']??20),50);
        $dateFilter='';
        if($period==='month') $dateFilter="AND x.created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')";
        elseif($period==='week') $dateFilter="AND x.created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)";

        $leaders=cache_remember('leaderboard_'.$period,function()use($d,$dateFilter,$limit){
            return $d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,SUM(x.xp) as total_xp FROM user_xp x JOIN users u ON x.user_id=u.id WHERE u.`status`='active' $dateFilter GROUP BY u.id ORDER BY total_xp DESC LIMIT $limit");
        },60);

        // Add levels
        foreach($leaders as &$l){$l['level']=getLevel(intval($l['total_xp']));}unset($l);

        // Add rank for current user
        $myRank=null;
        if($uid){
            foreach($leaders as $i=>$l){if(intval($l['id'])===$uid){$myRank=$i+1;break;}}
        }
        ok('OK',['leaders'=>$leaders,'my_rank'=>$myRank]);
    }

    // History
    if($action==='history'){
        if(!$uid) fail('Auth required',401);
        $page=max(1,intval($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_xp WHERE user_id=?",[$uid])['c']);
        $rows=$d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
        echo json_encode(['success'=>true,'data'=>['history'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total]]]);exit;
    }

    // Achievements / badges available
    if($action==='achievements'){
        $achievements=[
            ['id'=>'first_post','name'=>'Bài viết đầu tiên','icon'=>'📝','xp'=>20,'desc'=>'Đăng bài viết đầu tiên'],
            ['id'=>'streak_7','name'=>'7 ngày liên tục','icon'=>'🔥','xp'=>50,'desc'=>'Check-in 7 ngày liên tục'],
            ['id'=>'streak_30','name'=>'30 ngày liên tục','icon'=>'💪','xp'=>200,'desc'=>'Check-in 30 ngày liên tục'],
            ['id'=>'100_likes','name'=>'100 thành công','icon'=>'💯','xp'=>100,'desc'=>'Nhận 100 lượt thành công'],
            ['id'=>'popular','name'=>'Bài hot','icon'=>'🔥','xp'=>50,'desc'=>'Bài viết đạt 50 thành công'],
            ['id'=>'helper','name'=>'Người giúp đỡ','icon'=>'🤝','xp'=>30,'desc'=>'Ghi chú 50 lần'],
            ['id'=>'social','name'=>'Kết nối','icon'=>'👥','xp'=>20,'desc'=>'Có 20 người theo dõi'],
        ];
        // Check which are earned
        if($uid){
            try{$earned=$d->fetchAll("SELECT badge_type FROM user_badges WHERE user_id=?",[$uid]);$earnedSet=array_flip(array_column($earned,'badge_type'));}catch(\Throwable $e){$earnedSet=[];}
            foreach($achievements as &$a){$a['earned']=isset($earnedSet[$a['id']]);}unset($a);
        }
        ok('OK',$achievements);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Daily check-in
    if($action==='checkin'){
        $today=date('Y-m-d');
        $existing=$d->fetchOne("SELECT id FROM user_xp WHERE user_id=? AND action='checkin' AND DATE(created_at)=?",[$uid,$today]);
        if($existing) fail('Bạn đã check-in hôm nay rồi!');

        $xp=5;
        // Update streak
        $streak=$d->fetchOne("SELECT current_streak,longest_streak,last_checkin FROM user_streaks WHERE user_id=?",[$uid]);
        if(!$streak){
            $pdo->prepare("INSERT INTO user_streaks (user_id,current_streak,longest_streak,last_checkin) VALUES (?,1,1,?)")->execute([$uid,$today]);
            $currentStreak=1;
        }else{
            $lastDate=$streak['last_checkin'];
            $yesterday=date('Y-m-d',strtotime('-1 day'));
            if($lastDate===$yesterday){
                $newStreak=intval($streak['current_streak'])+1;
                $longest=max($newStreak,intval($streak['longest_streak']));
                $d->query("UPDATE user_streaks SET current_streak=?,longest_streak=?,last_checkin=? WHERE user_id=?",[$newStreak,$longest,$today,$uid]);
                $currentStreak=$newStreak;
                // Bonus XP for streaks
                if($newStreak===7) $xp+=50;
                if($newStreak===30) $xp+=200;
                if($newStreak%7===0) $xp+=10; // Weekly bonus
            }else{
                $d->query("UPDATE user_streaks SET current_streak=1,last_checkin=? WHERE user_id=?",[$today,$uid]);
                $currentStreak=1;
            }
        }

        $pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,?,?,?,NOW())")->execute([$uid,'checkin',$xp,'Check-in ngày '.$today.(isset($currentStreak)&&$currentStreak>1?' (streak '.$currentStreak.')':'')]);
        $totalXp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$uid])['s']);

        ok('OK',['xp_earned'=>$xp,'total_xp'=>$totalXp,'streak'=>$currentStreak,'level'=>getLevel($totalXp),'message'=>'+'.$xp.' XP! Streak '.(isset($currentStreak)?$currentStreak:1).' ngày']);
    }

    // Award XP (internal use — called by other APIs or admin)
    if($action==='award'){
        $targetId=intval($input['user_id']??$uid);
        $xpAction=$input['action_type']??'custom';
        $xp=intval($input['xp']??0);
        $detail=trim($input['detail']??'');
        if($xp<=0) fail('XP phải > 0');
        $pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,?,?,?,NOW())")->execute([$targetId,$xpAction,$xp,$detail]);
        ok('+'.$xp.' XP');
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
