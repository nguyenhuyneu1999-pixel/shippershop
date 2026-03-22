<?php
// ShipperShop API v2 — User Mentions (@mentions in posts/comments)
// session removed: JWT auth only
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

// Autocomplete users for @mention
if($action==='suggest'||!$action){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<1) mn_ok('OK',[]);
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY fullname LIMIT 10",['%'.$q.'%']);
    mn_ok('OK',$users);
}

// My mentions (posts/comments where I was mentioned)
if($action==='my_mentions'){
    $uid=require_auth();
    $page=max(1,intval($_GET['page']??1));$limit=15;$offset=($page-1)*$limit;
    $mentions=$d->fetchAll("SELECT m.*,p.content as post_content,p.user_id as post_author_id,u.fullname as mentioned_by_name,u.avatar as mentioned_by_avatar FROM mentions m LEFT JOIN posts p ON m.post_id=p.id LEFT JOIN users u ON (SELECT user_id FROM posts WHERE id=m.post_id LIMIT 1)=u.id WHERE m.mentioned_user_id=? ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
    mn_ok('OK',$mentions);
}

// Extract and save mentions from text (called internally after post/comment create)
if($action==='extract'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $text=$input['text']??'';
    $postId=intval($input['post_id']??0);
    $commentId=intval($input['comment_id']??0);

    // Find @mentions — format: @[Name](userId)
    preg_match_all('/@\[([^\]]+)\]\((\d+)\)/', $text, $matches, PREG_SET_ORDER);
    $mentioned=[];
    foreach($matches as $m){
        $mentionedId=intval($m[2]);
        if($mentionedId && $mentionedId!==$uid && !in_array($mentionedId,$mentioned)){
            $pdo->prepare("INSERT IGNORE INTO mentions (post_id,comment_id,user_id,mentioned_user_id,created_at) VALUES (?,?,?,?,NOW())")->execute([$postId?:null,$commentId?:null,$uid,$mentionedId]);
            $mentioned[]=$mentionedId;
            // Notify
            try{
                $userName=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid])['fullname']??'User';
                $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'mention','Được nhắc đến',?,?,NOW())")->execute([$mentionedId,$userName.' đã nhắc đến bạn',json_encode(['post_id'=>$postId,'comment_id'=>$commentId,'user_id'=>$uid])]);
            }catch(\Throwable $e){}
        }
    }
    mn_ok('OK',['mentioned_count'=>count($mentioned)]);
}

mn_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
