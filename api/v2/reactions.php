<?php
// ShipperShop API v2 — Post Reactions (emoji reactions: like, love, fire, wow, sad)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';
$VALID_REACTIONS=['like','love','fire','wow','sad','angry'];

function rx_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rx_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get reactions for a post
    if(!$action||$action==='get'){
        $pid=intval($_GET['post_id']??0);
        if(!$pid) rx_fail('Missing post_id');
        $uid=optional_auth();

        // Counts by type
        $counts=$d->fetchAll("SELECT reaction,COUNT(*) as count FROM post_reactions WHERE post_id=? GROUP BY reaction",[$pid]);
        $countMap=[];$total=0;
        foreach($counts as $c){$countMap[$c['reaction']]=intval($c['count']);$total+=intval($c['count']);}

        // User's reaction
        $myReaction=null;
        if($uid){
            $r=$d->fetchOne("SELECT reaction FROM post_reactions WHERE post_id=? AND user_id=?",[$pid,$uid]);
            if($r) $myReaction=$r['reaction'];
        }

        // Recent reactors (top 5)
        $recent=$d->fetchAll("SELECT pr.reaction,u.id,u.fullname,u.avatar FROM post_reactions pr JOIN users u ON pr.user_id=u.id WHERE pr.post_id=? ORDER BY pr.created_at DESC LIMIT 5",[$pid]);

        rx_ok('OK',['counts'=>$countMap,'total'=>$total,'my_reaction'=>$myReaction,'recent'=>$recent]);
    }

    // Reactors list (for "who reacted" modal)
    if($action==='reactors'){
        $pid=intval($_GET['post_id']??0);
        $type=$_GET['type']??'';
        if(!$pid) rx_fail('Missing post_id');
        $w="pr.post_id=?";$p=[$pid];
        if($type&&in_array($type,$VALID_REACTIONS)){$w.=" AND pr.reaction=?";$p[]=$type;}
        $reactors=$d->fetchAll("SELECT pr.reaction,pr.created_at,u.id,u.fullname,u.avatar,u.shipping_company FROM post_reactions pr JOIN users u ON pr.user_id=u.id WHERE $w ORDER BY pr.created_at DESC LIMIT 50",$p);
        rx_ok('OK',$reactors);
    }

    rx_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Toggle reaction
    if(!$action||$action==='react'){
        $pid=intval($input['post_id']??0);
        $reaction=trim($input['reaction']??'like');
        if(!$pid) rx_fail('Missing post_id');
        if(!in_array($reaction,$VALID_REACTIONS)) rx_fail('Invalid reaction');

        $existing=$d->fetchOne("SELECT id,reaction FROM post_reactions WHERE post_id=? AND user_id=?",[$pid,$uid]);

        if($existing){
            if($existing['reaction']===$reaction){
                // Remove (unreact)
                $d->query("DELETE FROM post_reactions WHERE id=?",[$existing['id']]);
                // Also remove from likes table for backward compat
                $d->query("DELETE FROM likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
                $d->query("UPDATE posts SET likes_count=GREATEST(likes_count-1,0) WHERE id=?",[$pid]);
                rx_ok('Đã bỏ reaction',['action'=>'removed','reaction'=>null]);
            }else{
                // Change reaction
                $d->query("UPDATE post_reactions SET reaction=?,created_at=NOW() WHERE id=?",[$reaction,$existing['id']]);
                rx_ok('Đã đổi reaction',['action'=>'changed','reaction'=>$reaction]);
            }
        }else{
            // Add new reaction
            $pdo->prepare("INSERT INTO post_reactions (post_id,user_id,reaction,created_at) VALUES (?,?,?,NOW())")->execute([$pid,$uid,$reaction]);
            // Also add to likes table for backward compat
            try{$pdo->prepare("INSERT IGNORE INTO likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$uid]);}catch(\Throwable $e){}
            $d->query("UPDATE posts SET likes_count=likes_count+1 WHERE id=?",[$pid]);

            // Log activity
            try{$pdo->prepare("INSERT INTO activity_feed (user_id,action,target_type,target_id,detail,created_at) VALUES (?,'react','post',?,?,NOW())")->execute([$uid,$pid,$reaction]);}catch(\Throwable $e){}

            // Notify post author
            $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
            if($post&&intval($post['user_id'])!==$uid){
                $emojis=['like'=>'👍','love'=>'❤️','fire'=>'🔥','wow'=>'😮','sad'=>'😢','angry'=>'😠'];
                $emoji=$emojis[$reaction]??'👍';
                $userName=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid])['fullname']??'User';
                try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'reaction',?,?,?,NOW())")->execute([intval($post['user_id']),$emoji.' Reaction mới',$userName.' đã '.$emoji.' bài viết của bạn',json_encode(['post_id'=>$pid,'user_id'=>$uid,'reaction'=>$reaction])]);}catch(\Throwable $e){}
            }

            rx_ok('Đã thả reaction',['action'=>'added','reaction'=>$reaction]);
        }
    }

    rx_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
