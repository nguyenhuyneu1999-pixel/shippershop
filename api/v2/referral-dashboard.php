<?php
// ShipperShop API v2 — Referral Dashboard
// User's referral stats, earnings, invite link, leaderboard
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

function rd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// My referral dashboard
if(!$action||$action==='my'){
    $uid=require_auth();

    // Get or create referral code
    $code=$d->fetchOne("SELECT code FROM referral_codes WHERE user_id=?",[$uid]);
    if(!$code){
        $newCode='SS'.strtoupper(substr(md5($uid.time()),0,6));
        try{$d->getConnection()->prepare("INSERT INTO referral_codes (user_id,code,created_at) VALUES (?,?,NOW())")->execute([$uid,$newCode]);}catch(\Throwable $e){}
        $code=['code'=>$newCode];
    }

    // Stats
    $totalReferred=intval($d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE referrer_id=?",[$uid])['c']??0);
    $thisMonth=intval($d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE referrer_id=? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')",[$uid])['c']??0);

    // Recent referrals
    $recent=$d->fetchAll("SELECT rl.created_at,u.fullname,u.avatar FROM referral_logs rl JOIN users u ON rl.referred_id=u.id WHERE rl.referrer_id=? ORDER BY rl.created_at DESC LIMIT 10",[$uid]);

    // Earnings estimate (10% of referred user's first deposit)
    $earnings=intval($d->fetchOne("SELECT COALESCE(SUM(wt.amount),0) as s FROM wallet_transactions wt WHERE wt.user_id=? AND wt.type='referral_bonus'",[$uid])['s']??0);

    rd_ok('OK',[
        'code'=>$code['code'],
        'invite_url'=>'https://shippershop.vn/r/'.$code['code'],
        'total_referred'=>$totalReferred,
        'this_month'=>$thisMonth,
        'earnings'=>$earnings,
        'recent'=>$recent,
    ]);
}

// Public leaderboard
if($action==='leaderboard'){
    $top=$d->fetchAll("SELECT rc.user_id,u.fullname,u.avatar,COUNT(rl.id) as referrals FROM referral_codes rc JOIN users u ON rc.user_id=u.id LEFT JOIN referral_logs rl ON rc.user_id=rl.referrer_id GROUP BY rc.user_id ORDER BY referrals DESC LIMIT 20");
    rd_ok('OK',$top);
}

rd_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
