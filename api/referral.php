<?php
/**
 * REFERRAL API - Viral Growth Engine
 * GET  ?action=my_code       - Lấy mã giới thiệu
 * GET  ?action=stats         - Thống kê referral  
 * GET  ?action=leaderboard   - Top người giới thiệu
 * POST ?action=apply         - Áp dụng mã (khi đăng ký)
 */
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}
$d=db();$action=$_GET['action']??'';
function rOk($data=null,$msg='OK'){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rErr($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg],JSON_UNESCAPED_UNICODE);exit;}

function genRefCode($uid){
    $d=db();$u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
    $n=strtoupper(substr(preg_replace('/[^a-zA-Z]/','',($u['fullname']??'SHIP')),0,4));
    if(strlen($n)<3)$n='SHIP';
    $code=$n.'-'.strtoupper(substr(md5($uid.time()),0,4));
    if($d->fetchOne("SELECT id FROM referral_codes WHERE code=?",[$code]))$code=$n.'-'.strtoupper(substr(md5($uid.microtime()),0,5));
    return $code;
}

function updateXP($uid,$action,$xp,$detail=''){
    $d=db();
    $d->query("INSERT INTO user_xp(user_id,action,xp,detail)VALUES(?,?,?,?)",[$uid,$action,$xp,$detail]);
    $total=$d->fetchOne("SELECT COALESCE(SUM(xp),0)as t FROM user_xp WHERE user_id=?",[$uid])['t'];
    $lv=1;if($total>=15000)$lv=5;elseif($total>=5000)$lv=4;elseif($total>=2000)$lv=3;elseif($total>=500)$lv=2;
    $d->query("INSERT INTO user_streaks(user_id,total_xp,level)VALUES(?,?,?)ON DUPLICATE KEY UPDATE total_xp=?,level=?",[$uid,$total,$lv,$total,$lv]);
    return ['total_xp'=>(int)$total,'level'=>$lv,'xp_added'=>$xp];
}

if($action==='my_code'){
    $uid=getAuthUserId();
    $ref=$d->fetchOne("SELECT * FROM referral_codes WHERE user_id=?",[$uid]);
    if(!$ref){
        $code=genRefCode($uid);
        $d->query("INSERT INTO referral_codes(user_id,code)VALUES(?,?)",[$uid,$code]);
        $d->query("UPDATE users SET ref_code=? WHERE id=?",[$code,$uid]);
        $ref=$d->fetchOne("SELECT * FROM referral_codes WHERE user_id=?",[$uid]);
    }
    $referred=$d->fetchAll("SELECT rl.*,u.fullname,u.avatar FROM referral_logs rl JOIN users u ON rl.referred_id=u.id WHERE rl.referrer_id=? ORDER BY rl.created_at DESC LIMIT 50",[$uid]);
    rOk(['code'=>$ref['code'],'link'=>'https://shippershop.vn/r/'.$ref['code'],'uses_count'=>(int)$ref['uses_count'],'referred_users'=>$referred,'rewards'=>['per_referral'=>'3 ngày Plus free','milestone_5'=>'1 tháng Plus free','milestone_20'=>'Badge Mentor vĩnh viễn']]);
}

if($action==='stats'){
    $uid=getAuthUserId();
    $total=$d->fetchOne("SELECT COUNT(*)as c FROM referral_logs WHERE referrer_id=?",[$uid])['c'];
    $month=$d->fetchOne("SELECT COUNT(*)as c FROM referral_logs WHERE referrer_id=? AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')",[$uid])['c'];
    $streak=$d->fetchOne("SELECT * FROM user_streaks WHERE user_id=?",[$uid]);
    rOk(['total_referrals'=>(int)$total,'this_month'=>(int)$month,'milestones'=>[['count'=>5,'reward'=>'1 tháng Plus free','achieved'=>$total>=5],['count'=>20,'reward'=>'Badge Mentor','achieved'=>$total>=20],['count'=>50,'reward'=>'Plus vĩnh viễn','achieved'=>$total>=50]],'xp'=>$streak?['total'=>(int)$streak['total_xp'],'level'=>(int)$streak['level'],'streak'=>(int)$streak['current_streak']]:['total'=>0,'level'=>1,'streak'=>0]]);
}

if($action==='leaderboard'){
    $period=$_GET['period']??'all';
    $df='';if($period==='month')$df="AND rl.created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')";elseif($period==='week')$df="AND rl.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)";
    $leaders=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(rl.id)as referral_count,COALESCE(us.total_xp,0)as total_xp,COALESCE(us.level,1)as level FROM referral_logs rl JOIN users u ON rl.referrer_id=u.id LEFT JOIN user_streaks us ON u.id=us.user_id WHERE 1=1 $df GROUP BY rl.referrer_id ORDER BY referral_count DESC LIMIT 20");
    rOk($leaders);
}

if($action==='apply'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $code=trim($input['code']??'');$newUid=intval($input['user_id']??0);
    if(!$code||!$newUid)rErr('Thiếu mã hoặc user_id');
    $ref=$d->fetchOne("SELECT * FROM referral_codes WHERE code=?",[$code]);
    if(!$ref)rErr('Mã giới thiệu không hợp lệ');
    if($ref['user_id']==$newUid)rErr('Không thể tự giới thiệu');
    $exists=$d->fetchOne("SELECT id FROM referral_logs WHERE referred_id=?",[$newUid]);
    if($exists)rErr('Đã sử dụng mã giới thiệu');
    $d->query("INSERT INTO referral_logs(referrer_id,referred_id,code)VALUES(?,?,?)",[$ref['user_id'],$newUid,$code]);
    $d->query("UPDATE referral_codes SET uses_count=uses_count+1 WHERE id=?",[$ref['id']]);
    $d->query("UPDATE users SET referred_by=? WHERE id=?",[$ref['user_id'],$newUid]);
    $xpResult=updateXP($ref['user_id'],'referral',100,'Giới thiệu user #'.$newUid);
    try{$d->query("INSERT INTO notifications(user_id,type,title,body,url,created_at)VALUES(?,'referral','Có người đăng ký qua mã!','+100 XP','/profile.html',NOW())",[$ref['user_id']]);}catch(Throwable$e){}
    rOk(['referrer_id'=>$ref['user_id'],'xp'=>$xpResult]);
}

rErr('Invalid action',404);
