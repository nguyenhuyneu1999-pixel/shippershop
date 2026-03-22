<?php
// ShipperShop API v2 — Smart Follow Suggestions
// Friends-of-friends, same company, most active, recently joined
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

$d=db();$action=$_GET['action']??'';$limit=min(intval($_GET['limit']??10),30);

function fs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Smart suggestions (mixed sources)
if(!$action||$action==='smart'){
    $data=cache_remember('follow_suggest_'.$uid, function() use($d,$uid,$limit) {
        $results=[];$seen=[$uid];

        // 1. Friends of friends (people followed by people I follow)
        $fof=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_verified,u.bio,
            COUNT(DISTINCT f1.follower_id) as mutual_count,'friends_of_friends' as source
            FROM follows f1
            JOIN follows f2 ON f1.following_id=f2.follower_id
            JOIN users u ON f2.following_id=u.id
            WHERE f1.follower_id=? AND f2.following_id!=?
            AND f2.following_id NOT IN (SELECT following_id FROM follows WHERE follower_id=?)
            AND f2.following_id NOT IN (SELECT blocked_id FROM user_blocks WHERE blocker_id=?)
            AND u.`status`='active'
            GROUP BY u.id ORDER BY mutual_count DESC LIMIT 8",[$uid,$uid,$uid,$uid]);
        foreach($fof as $f){$results[]=$f;$seen[]=intval($f['id']);}

        // 2. Same shipping company
        $myCompany=$d->fetchOne("SELECT shipping_company FROM users WHERE id=?",[$uid]);
        if($myCompany&&$myCompany['shipping_company']){
            $sameComp=$d->fetchAll("SELECT id,fullname,avatar,shipping_company,is_verified,bio,'same_company' as source
                FROM users WHERE shipping_company=? AND id!=? AND `status`='active'
                AND id NOT IN (SELECT following_id FROM follows WHERE follower_id=?)
                ORDER BY (SELECT COUNT(*) FROM follows WHERE following_id=users.id) DESC LIMIT 5",
                [$myCompany['shipping_company'],$uid,$uid]);
            foreach($sameComp as $s){
                if(!in_array(intval($s['id']),$seen)){$results[]=$s;$seen[]=intval($s['id']);}
            }
        }

        // 3. Most active recently (not followed)
        $seenPh=$seen?implode(',',array_map('intval',$seen)):'0';
        $active=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_verified,u.bio,
            COUNT(p.id) as recent_posts,'active_user' as source
            FROM users u JOIN posts p ON u.id=p.user_id
            WHERE u.`status`='active' AND u.id NOT IN ($seenPh)
            AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=$uid)
            AND p.created_at>DATE_SUB(NOW(),INTERVAL 7 DAY) AND p.`status`='active'
            GROUP BY u.id ORDER BY recent_posts DESC LIMIT 5");
        foreach($active as $a){
            if(!in_array(intval($a['id']),$seen)){$results[]=$a;$seen[]=intval($a['id']);}
        }

        // 4. Recently joined
        $seenPh2=implode(',',array_map('intval',$seen));
        $newUsers=$d->fetchAll("SELECT id,fullname,avatar,shipping_company,is_verified,bio,'new_user' as source
            FROM users WHERE `status`='active' AND id NOT IN ($seenPh2)
            AND id NOT IN (SELECT following_id FROM follows WHERE follower_id=$uid)
            ORDER BY created_at DESC LIMIT 3");
        foreach($newUsers as $n){$results[]=$n;}

        return array_slice($results,0,$limit);
    }, 300);

    // Add mutual count labels
    $sourceLabels=['friends_of_friends'=>'bạn chung','same_company'=>'cùng hãng','active_user'=>'hoạt động','new_user'=>'mới tham gia'];
    foreach($data as &$d2){
        $src=$d2['source']??'';
        $d2['reason']=$sourceLabels[$src]??'gợi ý';
        if($src==='friends_of_friends'&&isset($d2['mutual_count'])) $d2['reason']=$d2['mutual_count'].' bạn chung';
    }unset($d2);

    fs_ok('OK',$data);
}

// Dismiss suggestion (don't show again)
if($action==='dismiss'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $targetId=intval($input['user_id']??0);
    if($targetId){
        $key='dismissed_suggest_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $list=$row?json_decode($row['value'],true):[];
        $list[]=$targetId;$list=array_unique($list);
        if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($list),$key]);}
        else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($list)]);}
        cache_delete('follow_suggest_'.$uid);
    }
    fs_ok('OK');
}

fs_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
