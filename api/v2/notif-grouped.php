<?php
// ShipperShop API v2 — Grouped Notifications
// Collapse similar notifications: "A, B và 3 người khác đã thành công bài viết của bạn"
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ng_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$limit=min(intval($_GET['limit']??20),50);
$page=max(1,intval($_GET['page']??1));
$offset=($page-1)*$limit;

// Fetch raw notifications
$notifs=$d->fetchAll("SELECT n.*,u.fullname as actor_name,u.avatar as actor_avatar FROM notifications n LEFT JOIN users u ON JSON_UNQUOTE(JSON_EXTRACT(n.data,'$.user_id'))=u.id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 100",[$uid]);

// Group by type + target within 24h windows
$groups=[];$seen=[];
foreach($notifs as $n){
    $data=json_decode($n['data']??'{}',true);
    $targetId=$data['post_id']??$data['group_id']??$data['conversation_id']??0;
    $groupKey=$n['type'].'_'.$targetId;

    // Check if recent similar exists (within 24h)
    $found=false;
    foreach($groups as &$g){
        if($g['group_key']===$groupKey&&(strtotime($n['created_at'])-strtotime($g['oldest']))< 86400){
            $g['count']++;
            $g['actors'][]=['name'=>$n['actor_name'],'avatar'=>$n['actor_avatar']];
            if(strtotime($n['created_at'])>strtotime($g['latest'])) $g['latest']=$n['created_at'];
            $g['notification_ids'][]=$n['id'];
            $found=true;break;
        }
    }unset($g);

    if(!$found){
        $groups[]=[
            'group_key'=>$groupKey,
            'type'=>$n['type'],
            'title'=>$n['title'],
            'message'=>$n['message'],
            'data'=>$data,
            'target_id'=>$targetId,
            'actors'=>[['name'=>$n['actor_name'],'avatar'=>$n['actor_avatar']]],
            'count'=>1,
            'latest'=>$n['created_at'],
            'oldest'=>$n['created_at'],
            'is_read'=>intval($n['is_read']),
            'notification_ids'=>[$n['id']],
        ];
    }
}

// Build display messages
foreach($groups as &$g){
    if($g['count']>1){
        $names=array_column(array_slice($g['actors'],0,2),'name');
        $nameStr=implode(', ',$names);
        if($g['count']>2) $nameStr.=' và '.($g['count']-2).' người khác';
        $typeLabels=['reaction'=>'đã thả reaction','like'=>'đã thành công','comment'=>'đã ghi chú','follow'=>'đã theo dõi bạn','mention'=>'đã nhắc đến bạn','message'=>'đã gửi tin nhắn'];
        $g['grouped_message']=$nameStr.' '.($typeLabels[$g['type']]??$g['type']);
    }else{
        $g['grouped_message']=$g['message'];
    }
}unset($g);

// Sort by latest, paginate
usort($groups,function($a,$b){return strtotime($b['latest'])-strtotime($a['latest']);});
$total=count($groups);
$paged=array_slice($groups,$offset,$limit);

// Unread count
$unread=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0",[$uid])['c']);

ng_ok('OK',['notifications'=>$paged,'total'=>$total,'unread'=>$unread,'page'=>$page]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
