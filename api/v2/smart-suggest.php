<?php
// ShipperShop API v2 — Smart Follow Suggestions
// Scores users by: mutual follows, same company, activity, location proximity
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

function ss_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$limit=min(intval($_GET['limit']??10),30);

$data=cache_remember('smart_suggest_'.$uid, function() use($d,$uid) {
    $me=$d->fetchOne("SELECT shipping_company,province,district FROM users WHERE id=?",[$uid]);
    $myCompany=$me['shipping_company']??'';
    $myProvince=$me['province']??'';

    // Get users I don't follow yet
    $candidates=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.province,u.total_posts,u.total_success,u.is_verified,u.created_at FROM users u WHERE u.id!=? AND u.`status`='active' AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) ORDER BY u.total_posts DESC LIMIT 100",[$uid,$uid]);

    // My following list (for mutual calculation)
    $myFollowing=$d->fetchAll("SELECT following_id FROM follows WHERE follower_id=?",[$uid]);
    $myFollowingIds=array_column($myFollowing,'following_id');

    // Score each candidate
    $scored=[];
    foreach($candidates as $c){
        $score=0;$reasons=[];

        // Mutual follows (people who follow me that this person also follows)
        $mutuals=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows f1 JOIN follows f2 ON f1.following_id=f2.following_id WHERE f1.follower_id=? AND f2.follower_id=? AND f1.following_id!=? AND f1.following_id!=?",[$uid,$c['id'],$uid,$c['id']])['c']);
        if($mutuals>0){$score+=$mutuals*15;$reasons[]=$mutuals.' người theo dõi chung';}

        // Same shipping company
        if($myCompany&&$c['shipping_company']&&$myCompany===$c['shipping_company']){
            $score+=20;$reasons[]='Cùng hãng '.$myCompany;
        }

        // Same province
        if($myProvince&&$c['province']&&$myProvince===$c['province']){
            $score+=10;$reasons[]='Cùng khu vực';
        }

        // Activity level
        $posts=intval($c['total_posts']);
        if($posts>=50){$score+=10;$reasons[]='Rất tích cực';}
        elseif($posts>=10){$score+=5;}

        // Verified bonus
        if(intval($c['is_verified'])){$score+=8;$reasons[]='Đã xác minh';}

        // Delivery success
        if(intval($c['total_success'])>=50){$score+=5;}

        $c['score']=$score;
        $c['reasons']=$reasons;
        $scored[]=$c;
    }

    // Sort by score desc
    usort($scored,function($a,$b){return $b['score']-$a['score'];});
    return array_slice($scored,0,30);
}, 600);

ss_ok('OK',array_slice($data,0,$limit));

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
