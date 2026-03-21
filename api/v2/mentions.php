<?php
// ShipperShop API v2 — @Mentions
// Extract mentions from content, notify users, search mentionable users
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

function mn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mn_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Search users for @mention autocomplete
if($action==='search'){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<1) mn_ok('OK',[]);
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 10",['%'.$q.'%']);
    mn_ok('OK',$users);
}

// My mentions (posts/comments where I was @mentioned)
if($action==='my'||!$action){
    $uid=require_auth();
    $page=max(1,intval($_GET['page']??1));$limit=15;$offset=($page-1)*$limit;
    $mentions=$d->fetchAll("SELECT m.*,p.content as post_content,p.user_id as post_author_id,u.fullname as mentioner_name,u.avatar as mentioner_avatar FROM mentions m LEFT JOIN posts p ON m.post_id=p.id LEFT JOIN users u ON m.mentioner_user_id=u.id WHERE m.mentioned_user_id=? ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM mentions WHERE mentioned_user_id=?",[$uid])['c']);
    mn_ok('OK',['mentions'=>$mentions,'total'=>$total]);
}

// Process mentions in content (called internally after post/comment create)
if($action==='process'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $content=$input['content']??'';
    $postId=intval($input['post_id']??0);
    $commentId=intval($input['comment_id']??0);

    // Extract @mentions
    preg_match_all('/@(\S+)/', $content, $matches);
    $mentioned=0;
    foreach($matches[1] as $name){
        $user=$d->fetchOne("SELECT id FROM users WHERE fullname LIKE ? AND `status`='active' LIMIT 1",['%'.$name.'%']);
        if($user&&intval($user['id'])!==$uid){
            $mid=intval($user['id']);
            try{$pdo->prepare("INSERT IGNORE INTO mentions (post_id,comment_id,mentioned_user_id,mentioner_user_id,created_at) VALUES (?,?,?,?,NOW())")->execute([$postId?:null,$commentId?:null,$mid,$uid]);}catch(\Throwable $e){}
            // Notify
            try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'mention','Được nhắc đến',?,?,NOW())")->execute([$mid,getUserName($uid).' đã nhắc đến bạn',json_encode(['post_id'=>$postId,'comment_id'=>$commentId,'user_id'=>$uid])]);}catch(\Throwable $e){}
            $mentioned++;
        }
    }
    mn_ok('OK',['mentioned'=>$mentioned]);
}

mn_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}

function getUserName($uid){$u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);return $u?$u['fullname']:'User';}
