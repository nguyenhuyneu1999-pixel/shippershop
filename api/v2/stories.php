<?php
// ShipperShop API v2 — Stories (24h expiring content, like Instagram/WhatsApp Status)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function st_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function st_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Feed: stories from followed users + own (grouped by user)
    if(!$action||$action==='feed'){
        $limit=min(intval($_GET['limit']??30),50);
        $w="s.expires_at > NOW()";
        if($uid){
            // Own + followed users' stories
            $stories=$d->fetchAll("SELECT s.*,u.fullname as user_name,u.avatar as user_avatar,
                (SELECT COUNT(*) FROM story_views WHERE story_id=s.id) as view_count_real
                FROM stories s
                JOIN users u ON s.user_id=u.id
                WHERE $w AND (s.user_id=? OR s.user_id IN (SELECT following_id FROM follows WHERE follower_id=?))
                ORDER BY s.user_id=? DESC, s.created_at DESC
                LIMIT $limit",[$uid,$uid,$uid]);

            // Check which stories current user has viewed
            if($stories){
                $sids=array_column($stories,'id');
                $ph=implode(',',array_fill(0,count($sids),'?'));
                $viewed=$d->fetchAll("SELECT story_id FROM story_views WHERE user_id=? AND story_id IN ($ph)",array_merge([$uid],$sids));
                $viewedSet=array_flip(array_column($viewed,'story_id'));
                foreach($stories as &$s){$s['viewed']=isset($viewedSet[$s['id']]);}unset($s);
            }
        }else{
            $stories=$d->fetchAll("SELECT s.*,u.fullname as user_name,u.avatar as user_avatar FROM stories s JOIN users u ON s.user_id=u.id WHERE $w ORDER BY s.created_at DESC LIMIT $limit");
        }

        // Group by user
        $grouped=[];
        foreach($stories as $s){
            $uid2=intval($s['user_id']);
            if(!isset($grouped[$uid2])){
                $grouped[$uid2]=['user_id'=>$uid2,'user_name'=>$s['user_name'],'user_avatar'=>$s['user_avatar'],'stories'=>[],'has_unviewed'=>false];
            }
            $grouped[$uid2]['stories'][]=$s;
            if(!($s['viewed']??false)) $grouped[$uid2]['has_unviewed']=true;
        }
        st_ok('OK',array_values($grouped));
    }

    // Single story
    if($action==='detail'){
        $sid=intval($_GET['id']??0);
        if(!$sid) st_fail('Missing id');
        $story=$d->fetchOne("SELECT s.*,u.fullname as user_name,u.avatar as user_avatar FROM stories s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.expires_at > NOW()",[$sid]);
        if(!$story) st_fail('Story expired or not found',404);
        // Views
        $story['viewers']=$d->fetchAll("SELECT u.id,u.fullname,u.avatar FROM story_views sv JOIN users u ON sv.user_id=u.id WHERE sv.story_id=? ORDER BY sv.created_at DESC LIMIT 50",[$sid]);
        st_ok('OK',$story);
    }

    // User's stories
    if($action==='user'){
        $tid=intval($_GET['user_id']??0);
        if(!$tid) st_fail('Missing user_id');
        $stories=$d->fetchAll("SELECT * FROM stories WHERE user_id=? AND expires_at > NOW() ORDER BY created_at DESC",[$tid]);
        st_ok('OK',$stories);
    }

    st_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create story
    if($action==='create'){
        rate_enforce('story_create',10,3600);
        $content=trim($input['content']??'');
        $imageUrl=trim($input['image_url']??'');
        $videoUrl=trim($input['video_url']??'');
        $bg=$input['background']??'#7C3AED';
        $fontSize=intval($input['font_size']??18);
        $hours=min(intval($input['hours']??24),48);

        if(!$content&&!$imageUrl&&!$videoUrl) st_fail('Cần nội dung hoặc ảnh/video');

        // Validate background
        $validBgs=['#7C3AED','#EE4D2D','#22c55e','#3b82f6','#f59e0b','#ec4899','#1a1a2e','#000000','linear-gradient(135deg,#7C3AED,#EE4D2D)','linear-gradient(135deg,#22c55e,#3b82f6)','linear-gradient(135deg,#f59e0b,#ec4899)'];
        if(!in_array($bg,$validBgs)) $bg='#7C3AED';

        $expiresAt=date('Y-m-d H:i:s',time()+$hours*3600);
        $pdo->prepare("INSERT INTO stories (user_id,content,image_url,video_url,background,font_size,expires_at,created_at) VALUES (?,?,?,?,?,?,?,NOW())")->execute([$uid,$content?:null,$imageUrl?:null,$videoUrl?:null,$bg,$fontSize,$expiresAt]);
        $id=intval($pdo->lastInsertId());
        if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM stories");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        // Award XP
        try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'story',3,'Đăng story',NOW())")->execute([$uid]);}catch(\Throwable $e){}

        st_ok('Đã đăng story!',['id'=>$id,'expires_at'=>$expiresAt]);
    }

    // View story (mark as seen)
    if($action==='view'){
        $sid=intval($input['story_id']??0);
        if(!$sid) st_fail('Missing story_id');
        try{
            $pdo->prepare("INSERT IGNORE INTO story_views (story_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$sid,$uid]);
            $d->query("UPDATE stories SET view_count=view_count+1 WHERE id=?",[$sid]);
        }catch(\Throwable $e){}
        st_ok('OK');
    }

    // Delete story (own only)
    if($action==='delete'){
        $sid=intval($input['story_id']??0);
        $story=$d->fetchOne("SELECT user_id FROM stories WHERE id=?",[$sid]);
        if(!$story||intval($story['user_id'])!==$uid) st_fail('Không có quyền',403);
        $d->query("DELETE FROM stories WHERE id=?",[$sid]);
        $d->query("DELETE FROM story_views WHERE story_id=?",[$sid]);
        st_ok('Đã xóa');
    }

    st_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
