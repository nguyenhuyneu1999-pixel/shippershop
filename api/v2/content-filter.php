<?php
// ShipperShop API v2 — Content Filter (Auto-moderation)
// Keyword blacklist, spam detection, auto-hide/flag
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function cf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Default blacklist (admin can customize via settings)
function getBlacklist(){
    $d=db();
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='content_blacklist'");
    if($row) return json_decode($row['value'],true)?:[];
    return ['lừa đảo','scam','hack','casino','cá cược','cá độ','sex','porn','18+','slot','tín dụng đen','vay nóng','cho vay'];
}

try {

// Check content (called before post/comment publish)
if($action==='check'||(!$action&&$_SERVER['REQUEST_METHOD']==='POST')){
    $input=json_decode(file_get_contents('php://input'),true);
    $content=mb_strtolower(trim($input['content']??''));
    if(!$content) cf_ok('OK',['clean'=>true,'score'=>0]);

    $blacklist=getBlacklist();
    $found=[];$score=0;

    foreach($blacklist as $word){
        if(mb_strpos($content,mb_strtolower($word))!==false){
            $found[]=$word;
            $score+=10;
        }
    }

    // Spam patterns
    $spamPatterns=[
        '/(\d{10,})/'=>5,           // Long number strings (phone spam)
        '/(http|www\.)\S{50,}/i'=>8, // Very long URLs
        '/(.)\1{5,}/'=>3,            // Repeated chars
        '/[A-Z]{10,}/'=>3,           // ALL CAPS spam
    ];
    foreach($spamPatterns as $pattern=>$points){
        if(preg_match($pattern,$content)){$score+=$points;$found[]='spam_pattern';}
    }

    $action_taken='none';
    if($score>=20){$action_taken='block';}
    elseif($score>=10){$action_taken='flag';}

    cf_ok('OK',['clean'=>$score<10,'score'=>$score,'flagged_words'=>$found,'action'=>$action_taken]);
}

// Admin: get/update blacklist
if($_SERVER['REQUEST_METHOD']==='GET'){
    if($action==='blacklist'){
        $uid=require_auth();
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') cf_fail('Admin only',403);
        cf_ok('OK',['words'=>getBlacklist()]);
    }
    cf_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='update_blacklist'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') cf_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);
    $words=$input['words']??[];
    if(!is_array($words)) cf_fail('Invalid format');
    $key='content_blacklist';
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($words),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($words)]);
    cf_ok('Đã cập nhật',['count'=>count($words)]);
}

cf_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
