<?php
// ShipperShop API v2 — Admin Fraud Detection
// Detect suspicious patterns: fake likes, bot accounts, engagement manipulation
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

$d=db();

function fd2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fd2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') fd2_fail('Admin only',403);

$data=cache_remember('fraud_detection', function() use($d) {
    $alerts=[];

    // 1. Users with suspiciously high like ratio
    $highLikers=$d->fetchAll("SELECT u.id,u.fullname,COUNT(pl.id) as likes_given,(SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active') as posts FROM users u JOIN post_likes pl ON pl.user_id=u.id WHERE pl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY u.id HAVING likes_given > 50 AND posts < 3 ORDER BY likes_given DESC LIMIT 5");
    foreach($highLikers as $h){$alerts[]=['type'=>'high_liker','severity'=>'medium','user_id'=>intval($h['id']),'user_name'=>$h['fullname'],'detail'=>$h['likes_given'].' likes / '.$h['posts'].' posts in 7d'];}

    // 2. Rapid-fire posting (>10 posts in 1 hour)
    $rapidPosters=$d->fetchAll("SELECT user_id, COUNT(*) as cnt, MIN(created_at) as first_post FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY user_id, DATE(created_at), HOUR(created_at) HAVING cnt > 10 ORDER BY cnt DESC LIMIT 5");
    foreach($rapidPosters as $rp){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$rp['user_id']]);
        $alerts[]=['type'=>'rapid_post','severity'=>'high','user_id'=>intval($rp['user_id']),'user_name'=>$u?$u['fullname']:'','detail'=>$rp['cnt'].' posts in 1 hour'];
    }

    // 3. Duplicate content
    $dupes=$d->fetchAll("SELECT content, COUNT(*) as cnt, GROUP_CONCAT(DISTINCT user_id) as users FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY LEFT(content,100) HAVING cnt >= 3 ORDER BY cnt DESC LIMIT 5");
    foreach($dupes as $dup){$alerts[]=['type'=>'duplicate','severity'=>'low','detail'=>$dup['cnt'].' copies: '.mb_substr($dup['content'],0,50),'users'=>$dup['users']];}

    // 4. Failed login spikes
    $loginSpikes=$d->fetchAll("SELECT ip, COUNT(*) as attempts FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY ip HAVING attempts >= 10 ORDER BY attempts DESC LIMIT 5");
    foreach($loginSpikes as $ls){$alerts[]=['type'=>'brute_force','severity'=>'critical','detail'=>$ls['attempts'].' failed logins from '.$ls['ip']];}

    $severityCounts=['critical'=>0,'high'=>0,'medium'=>0,'low'=>0];
    foreach($alerts as $a){$severityCounts[$a['severity']]=($severityCounts[$a['severity']]??0)+1;}
    $riskLevel=($severityCounts['critical']>0)?'critical':(($severityCounts['high']>0)?'high':(count($alerts)>3?'medium':'low'));

    return ['alerts'=>$alerts,'risk_level'=>$riskLevel,'severity_counts'=>$severityCounts,'total_alerts'=>count($alerts)];
}, 600);

fd2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
