<?php
// ShipperShop API v2 — User Activity Timeline
// Public activity feed: posts, comments, likes, joins, achievements
// session removed: JWT auth only
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

function tl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId) tl_ok('OK',[]);
$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??20),50);
$offset=($page-1)*$limit;

$events=[];

// Posts
$posts=$d->fetchAll("SELECT id,'post' as event_type,content,likes_count,comments_count,created_at FROM posts WHERE user_id=? AND `status`='active' ORDER BY created_at DESC LIMIT $limit OFFSET $offset",[$userId]);
foreach($posts as $p){
    $events[]=['type'=>'post','id'=>$p['id'],'text'=>mb_substr($p['content'],0,150),'likes'=>intval($p['likes_count']),'comments'=>intval($p['comments_count']),'icon'=>'📝','label'=>'Đăng bài viết','time'=>$p['created_at']];
}

// Comments (last 20)
$comments=$d->fetchAll("SELECT c.id,c.content,c.post_id,c.created_at FROM comments c WHERE c.user_id=? ORDER BY c.created_at DESC LIMIT 20 OFFSET $offset",[$userId]);
foreach($comments as $c){
    $events[]=['type'=>'comment','id'=>$c['id'],'text'=>mb_substr($c['content'],0,100),'post_id'=>intval($c['post_id']),'icon'=>'💬','label'=>'Ghi chú','time'=>$c['created_at']];
}

// Group joins
$joins=$d->fetchAll("SELECT gm.group_id,g.name,gm.joined_at as created_at FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY gm.joined_at DESC LIMIT 10",[$userId]);
foreach($joins as $j){
    $events[]=['type'=>'group_join','group_id'=>intval($j['group_id']),'text'=>'Tham gia nhóm '.$j['name'],'icon'=>'👥','label'=>'Tham gia nhóm','time'=>$j['created_at']];
}

// Sort all events by time desc
usort($events,function($a,$b){return strcmp($b['time'],$a['time']);});
$events=array_slice($events,0,$limit);

$total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$userId])['c']);

tl_ok('OK',['events'=>$events,'total'=>$total,'page'=>$page,'has_more'=>count($events)>=$limit]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
