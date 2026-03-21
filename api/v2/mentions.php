<?php
// ShipperShop API v2 — User Mentions (@username autocomplete + linking)
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

$d=db();$action=$_GET['action']??'';

function mt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Autocomplete @mention (search users by name)
if(!$action||$action==='search'){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<1){mt_ok('OK',[]);}
    $users=cache_remember('mention_'.md5($q), function() use($d,$q) {
        return $d->fetchAll("SELECT id,fullname,avatar,shipping_company,is_verified FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 8",['%'.$q.'%']);
    }, 60);
    mt_ok('OK',$users);
}

// Get my mentions (posts/comments that mention me)
if($action==='my'){
    $uid=require_auth();
    $page=max(1,intval($_GET['page']??1));$limit=15;$offset=($page-1)*$limit;
    // Search posts containing user's name with @
    $user=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
    if(!$user){mt_ok('OK',[]);}
    $name=$user['fullname'];
    $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.content LIKE ? ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset",['%@'.$name.'%']);
    mt_ok('OK',$posts);
}

mt_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
