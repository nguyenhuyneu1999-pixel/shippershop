<?php
// ShipperShop API v2 — Profile Completeness Score
// Calculate and return profile completion percentage + suggestions
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

function ps_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$u=$d->fetchOne("SELECT * FROM users WHERE id=?",[$uid]);
if(!$u) ps_ok('OK',['score'=>0]);

$checks=[
    ['key'=>'fullname','label'=>'Tên đầy đủ','weight'=>15,'done'=>!empty($u['fullname'])&&mb_strlen($u['fullname'])>=2],
    ['key'=>'avatar','label'=>'Ảnh đại diện','weight'=>15,'done'=>!empty($u['avatar'])&&strpos($u['avatar'],'default')===false],
    ['key'=>'email','label'=>'Email','weight'=>10,'done'=>!empty($u['email'])],
    ['key'=>'phone','label'=>'Số điện thoại','weight'=>10,'done'=>!empty($u['phone'])],
    ['key'=>'shipping_company','label'=>'Hãng vận chuyển','weight'=>15,'done'=>!empty($u['shipping_company'])],
    ['key'=>'bio','label'=>'Giới thiệu bản thân','weight'=>10,'done'=>!empty($u['bio'])&&mb_strlen($u['bio'])>=10],
    ['key'=>'first_post','label'=>'Đăng bài đầu tiên','weight'=>10,'done'=>intval($u['total_posts']??0)>0],
    ['key'=>'first_follow','label'=>'Theo dõi ai đó','weight'=>5,'done'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$uid])['c'])>0],
    ['key'=>'verified','label'=>'Xác minh tài khoản','weight'=>10,'done'=>intval($u['is_verified']??0)>0],
];

$score=0;$maxScore=0;$suggestions=[];
foreach($checks as $c){
    $maxScore+=$c['weight'];
    if($c['done']) $score+=$c['weight'];
    else $suggestions[]=['key'=>$c['key'],'label'=>$c['label'],'weight'=>$c['weight']];
}
$pct=$maxScore>0?round($score/$maxScore*100):0;

// Update cached score
try{$d->query("UPDATE users SET profile_completion=? WHERE id=?",[$pct,$uid]);}catch(\Throwable $e){}

ps_ok('OK',['score'=>$pct,'completed'=>$score,'max'=>$maxScore,'checks'=>$checks,'suggestions'=>$suggestions,'next_step'=>!empty($suggestions)?$suggestions[0]:null]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
