<?php
/**
 * ShipperShop API v2 — Search (users, posts, groups, hashtags)
 */
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$action=$_GET['action']??'global';
$q=trim($_GET['q']??'');
$limit=min(intval($_GET['limit']??10),20);
$uid=optional_auth();

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

if(mb_strlen($q)<1&&$action!=='trending'&&$action!=='history'&&$action!=='clear_history'){
    ok('OK',['users'=>[],'posts'=>[],'groups'=>[]]);
}

if($action==='global'){
    $users=$d->fetchAll("SELECT id,fullname,avatar,username,shipping_company,total_success FROM users WHERE `status`='active' AND (fullname LIKE ? OR username LIKE ?) ORDER BY total_success DESC LIMIT 5",['%'.$q.'%','%'.$q.'%']);
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.created_at,u.fullname as user_name,u.avatar as user_avatar FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.content LIKE ? ORDER BY p.likes_count DESC LIMIT 5",['%'.$q.'%']);
    $groups=[];
    try{$groups=$d->fetchAll("SELECT id,name,description,avatar,member_count FROM `groups` WHERE name LIKE ? OR description LIKE ? ORDER BY member_count DESC LIMIT 5",['%'.$q.'%','%'.$q.'%']);}catch(\Throwable $e){$groups=[];}

    if($uid){
        $total=count($users)+count($posts)+count($groups);
        try{$pdo=$d->getConnection();$pdo->prepare("INSERT INTO search_history (user_id,query,result_count,created_at) VALUES (?,?,?,NOW())")->execute([$uid,$q,$total]);}catch(\Throwable $e){}
    }
    ok('OK',['users'=>$users,'posts'=>$posts,'groups'=>$groups]);
}

if($action==='users'){
    $users=$d->fetchAll("SELECT id,fullname,avatar,username,shipping_company,bio,total_success FROM users WHERE `status`='active' AND (fullname LIKE ? OR username LIKE ? OR shipping_company LIKE ?) ORDER BY total_success DESC LIMIT $limit",['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
    ok('OK',$users);
}

if($action==='posts'){
    $province=$_GET['province']??'';
    $w="p.`status`='active' AND p.content LIKE ?";$params=['%'.$q.'%'];
    if($province){$w.=" AND p.province LIKE ?";$params[]='%'.$province.'%';}
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,p.province,p.district,u.fullname as user_name,u.avatar as user_avatar FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE $w ORDER BY p.likes_count DESC LIMIT $limit",$params);
    ok('OK',$posts);
}

if($action==='groups'){
    $groups=[];
    try{$groups=$d->fetchAll("SELECT id,name,description,avatar,member_count FROM `groups` WHERE name LIKE ? OR description LIKE ? ORDER BY member_count DESC LIMIT $limit",['%'.$q.'%','%'.$q.'%']);}catch(\Throwable $e){}
    ok('OK',$groups);
}

if($action==='trending'){
    $posts=$d->fetchAll("SELECT content FROM posts WHERE `status`='active' AND created_at>DATE_SUB(NOW(),INTERVAL 7 DAY)");
    $tags=[];
    foreach($posts as $p){
        preg_match_all('/#([\p{L}\p{N}_]+)/u',$p['content'],$m);
        if(!empty($m[1])) foreach($m[1] as $tag){$t=mb_strtolower($tag);$tags[$t]=($tags[$t]??0)+1;}
    }
    arsort($tags);$tags=array_slice($tags,0,20,true);
    $result=[];foreach($tags as $tag=>$count){$result[]=['tag'=>$tag,'count'=>$count];}
    ok('OK',$result);
}

if($action==='history'){
    if(!$uid) ok('OK',[]);
    $rows=$d->fetchAll("SELECT query,result_count,created_at FROM search_history WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$uid]);
    ok('OK',$rows);
}

if($action==='clear_history'){
    if($uid) $d->query("DELETE FROM search_history WHERE user_id=?",[$uid]);
    ok('OK');
}

// Advanced search with filters
if($action==='advanced'){
    $type=$_GET['type']??'posts'; // posts, users, groups
    $sort=$_GET['sort']??'relevant'; // relevant, newest, popular
    $province=$_GET['province']??'';
    $company=$_GET['company']??'';
    $dateFrom=$_GET['date_from']??'';
    $dateTo=$_GET['date_to']??'';
    $hasImage=isset($_GET['has_image']);
    $hasVideo=isset($_GET['has_video']);
    $page=max(1,intval($_GET['page']??1));
    $offset=($page-1)*$limit;

    if($type==='posts'){
        $w="p.`status`='active' AND p.is_draft=0";$params=[];
        if($q){$w.=" AND p.content LIKE ?";$params[]='%'.$q.'%';}
        if($province){$w.=" AND p.province=?";$params[]=$province;}
        if($company){$w.=" AND u.shipping_company=?";$params[]=$company;}
        if($dateFrom){$w.=" AND p.created_at>=?";$params[]=$dateFrom;}
        if($dateTo){$w.=" AND p.created_at<=?";$params[]=$dateTo.' 23:59:59';}
        if($hasImage) $w.=" AND p.images IS NOT NULL AND p.images!='[]' AND p.images!=''";
        if($hasVideo) $w.=" AND p.video_url IS NOT NULL AND p.video_url!=''";
        $orderBy=['relevant'=>'(p.likes_count*3+p.comments_count*5) DESC','newest'=>'p.created_at DESC','popular'=>'p.likes_count DESC'][$sort]??'p.created_at DESC';
        $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company,u.is_verified FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE $w ORDER BY $orderBy LIMIT $limit OFFSET $offset",$params);
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE $w",$params)['c']);
        ok('OK',['posts'=>$posts,'total'=>$total,'page'=>$page]);
    }

    if($type==='users'){
        $w="u.`status`='active'";$params=[];
        if($q){$w.=" AND (u.fullname LIKE ? OR u.email LIKE ?)";$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
        if($company){$w.=" AND u.shipping_company=?";$params[]=$company;}
        $orderBy=['newest'=>'u.created_at DESC','popular'=>'(SELECT COUNT(*) FROM follows WHERE following_id=u.id) DESC'][$sort]??'u.fullname ASC';
        $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.bio,u.shipping_company,u.is_verified FROM users u WHERE $w ORDER BY $orderBy LIMIT $limit OFFSET $offset",$params);
        ok('OK',['users'=>$users,'page'=>$page]);
    }

    if($type==='groups'){
        $w="1=1";$params=[];
        if($q){$w.=" AND (g.name LIKE ? OR g.description LIKE ?)";$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
        $groups=$d->fetchAll("SELECT g.*,(SELECT COUNT(*) FROM group_members WHERE group_id=g.id) as member_count FROM `groups` g WHERE $w ORDER BY member_count DESC LIMIT $limit OFFSET $offset",$params);
        ok('OK',['groups'=>$groups,'page'=>$page]);
    }

    ok('OK',[]);
}

ok('OK',[]);

} catch(Throwable $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage(),'line'=>$e->getLine()]);
}
