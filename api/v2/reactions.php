<?php
// ShipperShop API v2 — Post Reactions (Facebook-style emoji reactions)
// like, love, haha, wow, sad, angry
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
$VALID_REACTIONS=['like','love','haha','wow','sad','angry'];

function rx_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rx_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get reactions for a post
    if($action==='post'||!$action){
        $pid=intval($_GET['post_id']??0);
        if(!$pid) rx_fail('Missing post_id');
        $uid=optional_auth();

        // Count per reaction type
        $counts=$d->fetchAll("SELECT reaction,COUNT(*) as count FROM post_reactions WHERE post_id=? GROUP BY reaction",[$pid]);
        $breakdown=[];$total=0;
        foreach($counts as $c){$breakdown[$c['reaction']]=intval($c['count']);$total+=intval($c['count']);}

        // Current user's reaction
        $myReaction=null;
        if($uid){
            $my=$d->fetchOne("SELECT reaction FROM post_reactions WHERE post_id=? AND user_id=?",[$pid,$uid]);
            if($my) $myReaction=$my['reaction'];
        }

        // Top reactors (first 5)
        $reactors=$d->fetchAll("SELECT pr.reaction,u.id,u.fullname,u.avatar FROM post_reactions pr JOIN users u ON pr.user_id=u.id WHERE pr.post_id=? ORDER BY pr.created_at DESC LIMIT 5",[$pid]);

        rx_ok('OK',['total'=>$total,'breakdown'=>$breakdown,'my_reaction'=>$myReaction,'reactors'=>$reactors]);
    }

    // Who reacted with specific type
    if($action==='list'){
        $pid=intval($_GET['post_id']??0);
        $type=$_GET['type']??'';
        if(!$pid) rx_fail('Missing post_id');
        $w="pr.post_id=?";$p=[$pid];
        if($type&&in_array($type,$VALID_REACTIONS)){$w.=" AND pr.reaction=?";$p[]=$type;}
        $users=$d->fetchAll("SELECT pr.reaction,u.id,u.fullname,u.avatar FROM post_reactions pr JOIN users u ON pr.user_id=u.id WHERE $w ORDER BY pr.created_at DESC LIMIT 50",$p);
        rx_ok('OK',$users);
    }

    rx_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // React/unreact
    if($action==='react'||!$action){
        $pid=intval($input['post_id']??0);
        $reaction=trim($input['reaction']??'like');
        if(!$pid) rx_fail('Missing post_id');
        if(!in_array($reaction,$VALID_REACTIONS)) rx_fail('Invalid reaction');

        $existing=$d->fetchOne("SELECT id,reaction FROM post_reactions WHERE post_id=? AND user_id=?",[$pid,$uid]);
        if($existing){
            if($existing['reaction']===$reaction){
                // Remove reaction
                $d->query("DELETE FROM post_reactions WHERE id=?",[$existing['id']]);
                $d->query("UPDATE posts SET likes_count=GREATEST(likes_count-1,0) WHERE id=?",[$pid]);
                rx_ok('Đã bỏ',['reacted'=>false,'reaction'=>null]);
            }else{
                // Change reaction
                $d->query("UPDATE post_reactions SET reaction=?,created_at=NOW() WHERE id=?",[$reaction,$existing['id']]);
                rx_ok('Đã đổi',['reacted'=>true,'reaction'=>$reaction]);
            }
        }else{
            // New reaction
            $pdo->prepare("INSERT INTO post_reactions (post_id,user_id,reaction,created_at) VALUES (?,?,?,NOW())")->execute([$pid,$uid,$reaction]);
            $d->query("UPDATE posts SET likes_count=likes_count+1 WHERE id=?",[$pid]);
            // Also insert into likes table for backward compatibility
            try{$pdo->prepare("INSERT IGNORE INTO likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$uid]);}catch(\Throwable $e){}
            // Notify post owner
            $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
            if($post&&intval($post['user_id'])!==$uid){
                $emojis=['like'=>'👍','love'=>'❤️','haha'=>'😂','wow'=>'😮','sad'=>'😢','angry'=>'😡'];
                $emoji=$emojis[$reaction]??'👍';
                try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'reaction',?,?,?,NOW())")->execute([intval($post['user_id']),$emoji.' Cảm xúc mới',getUserName($uid).' đã bày tỏ '.$emoji,json_encode(['post_id'=>$pid,'user_id'=>$uid,'reaction'=>$reaction])]);}catch(\Throwable $e){}
            }
            rx_ok('Đã bày tỏ!',['reacted'=>true,'reaction'=>$reaction]);
        }
    }

    rx_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}

function getUserName($uid){$u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);return $u?$u['fullname']:'User';}
