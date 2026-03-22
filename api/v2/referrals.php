<?php
// ShipperShop API v2 — Referrals
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

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // My referral code + stats
    if($action==='my_code'||!$action){
        if(!$uid) fail('Auth required',401);
        $code=$d->fetchOne("SELECT * FROM referral_codes WHERE user_id=?",[$uid]);
        if(!$code){
            // Generate new code
            $newCode=strtoupper(substr(md5($uid.time()),0,8));
            try{$pdo->prepare("INSERT INTO referral_codes (user_id,code,created_at) VALUES (?,?,NOW())")->execute([$uid,$newCode]);
            $code=['user_id'=>$uid,'code'=>$newCode,'uses'=>0];}catch(\Throwable $e){$code=null;}
        }
        $totalRefs=intval($d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE referrer_id=?",[$uid])['c']??0);
        ok('OK',['code'=>$code?$code['code']:'','link'=>'https://shippershop.vn/r/'.($code?$code['code']:''),'total_referrals'=>$totalRefs]);
    }

    // Referral leaderboard
    if($action==='leaderboard'){
        $limit=min(intval($_GET['limit']??20),50);
        $leaders=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(rl.id) as ref_count FROM referral_logs rl JOIN users u ON rl.referrer_id=u.id GROUP BY u.id ORDER BY ref_count DESC LIMIT $limit");
        ok('OK',$leaders);
    }

    // My referrals
    if($action==='my_referrals'){
        if(!$uid) fail('Auth required',401);
        $refs=$d->fetchAll("SELECT rl.*,u.fullname,u.avatar FROM referral_logs rl LEFT JOIN users u ON rl.referred_id=u.id WHERE rl.referrer_id=? ORDER BY rl.created_at DESC LIMIT 50",[$uid]);
        ok('OK',$refs);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Redeem referral code (called on register)
    if($action==='redeem'){
        $code=trim($input['code']??'');
        if(!$code) fail('Missing code');
        $ref=$d->fetchOne("SELECT * FROM referral_codes WHERE code=?",[$code]);
        if(!$ref) fail('Mã không hợp lệ');
        if(intval($ref['user_id'])===$uid) fail('Không thể dùng mã của chính mình');
        // Check not already referred
        $existing=$d->fetchOne("SELECT id FROM referral_logs WHERE referred_id=?",[$uid]);
        if($existing) fail('Bạn đã dùng mã giới thiệu rồi');
        // Log referral
        $pdo->prepare("INSERT INTO referral_logs (referrer_id,referred_id,code,created_at) VALUES (?,?,?,NOW())")->execute([intval($ref['user_id']),$uid,$code]);
        // Award XP to referrer
        try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'referral',20,?,NOW())")->execute([intval($ref['user_id']),'Giới thiệu user #'.$uid]);}catch(\Throwable $e){}
        // Award XP to referred user
        try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'referred',10,'Được giới thiệu bởi code '.$code,NOW())")->execute([$uid]);}catch(\Throwable $e){}
        ok('Đã áp dụng mã giới thiệu! +10 XP',['referrer_id'=>intval($ref['user_id'])]);
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
