<?php
// ShipperShop API v2 — Admin User Management
// Search, filter, bulk actions on users
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function au_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function au_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') au_fail('Admin only',403);

$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??20),100);
$offset=($page-1)*$limit;

// Search + filter users
if(!$action||$action==='search'){
    $q=trim($_GET['q']??'');
    $status=$_GET['status']??'';
    $company=$_GET['company']??'';
    $role=$_GET['role']??'';
    $verified=$_GET['verified']??'';
    $sort=$_GET['sort']??'newest';

    $w="1=1";$p=[];
    if($q){$w.=" AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ?)";$p[]='%'.$q.'%';$p[]='%'.$q.'%';$p[]='%'.$q.'%';}
    if($status){$w.=" AND `status`=?";$p[]=$status;}
    if($company){$w.=" AND shipping_company=?";$p[]=$company;}
    if($role){$w.=" AND role=?";$p[]=$role;}
    if($verified==='1') $w.=" AND is_verified=1";
    if($verified==='0') $w.=" AND is_verified=0";

    $orderBy=['newest'=>'created_at DESC','oldest'=>'created_at ASC','name'=>'fullname ASC','posts'=>'total_posts DESC','active'=>'last_active DESC'][$sort]??'created_at DESC';

    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE $w",$p)['c']);
    $users=$d->fetchAll("SELECT id,fullname,email,phone,avatar,shipping_company,role,`status`,is_verified,is_online,total_posts,total_followers,created_at,last_active FROM users WHERE $w ORDER BY $orderBy LIMIT $limit OFFSET $offset",$p);

    au_ok('OK',['users'=>$users,'total'=>$total,'page'=>$page,'per_page'=>$limit]);
}

// User detail (admin view)
if($action==='detail'){
    $tid=intval($_GET['user_id']??0);
    if(!$tid) au_fail('Missing user_id');
    $target=$d->fetchOne("SELECT * FROM users WHERE id=?",[$tid]);
    if(!$target) au_fail('Not found',404);
    unset($target['password']);

    // Extra stats
    $postCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$tid])['c']);
    $commentCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND `status`='active'",[$tid])['c']);
    $reportCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports pr JOIN posts p ON pr.post_id=p.id WHERE p.user_id=?",[$tid])['c']);
    $loginAttempts=$d->fetchAll("SELECT ip,success,created_at FROM login_attempts WHERE email=? ORDER BY created_at DESC LIMIT 10",[$target['email']]);
    $notes=$d->fetchAll("SELECT an.*,u.fullname as admin_name FROM admin_notes an LEFT JOIN users u ON an.admin_id=u.id WHERE an.target_user_id=? ORDER BY an.created_at DESC LIMIT 10",[$tid]);
    $sub=$d->fetchOne("SELECT us.*,sp.name as plan_name FROM user_subscriptions us LEFT JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' ORDER BY us.created_at DESC LIMIT 1",[$tid]);

    $target['stats']=['posts'=>$postCount,'comments'=>$commentCount,'reports_received'=>$reportCount];
    $target['login_attempts']=$loginAttempts;
    $target['admin_notes']=$notes;
    $target['subscription']=$sub;

    au_ok('OK',$target);
}

// Bulk action
if($action==='bulk'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $ids=$input['user_ids']??[];
    $bulkAction=$input['action']??'';
    if(!$ids||!is_array($ids)||!$bulkAction) au_fail('Missing data');
    if(count($ids)>50) au_fail('Max 50 users');

    $validActions=['ban','unban','verify','unverify','delete'];
    if(!in_array($bulkAction,$validActions)) au_fail('Invalid action');

    $count=0;
    foreach($ids as $tid){
        $tid=intval($tid);
        if($tid<2) continue; // Don't touch admin
        if($bulkAction==='ban'){$d->query("UPDATE users SET `status`='banned' WHERE id=? AND role!='admin'",[$tid]);$count++;}
        elseif($bulkAction==='unban'){$d->query("UPDATE users SET `status`='active' WHERE id=? AND `status`='banned'",[$tid]);$count++;}
        elseif($bulkAction==='verify'){$d->query("UPDATE users SET is_verified=1,verified_at=NOW() WHERE id=?",[$tid]);$count++;}
        elseif($bulkAction==='unverify'){$d->query("UPDATE users SET is_verified=0,verified_at=NULL WHERE id=?",[$tid]);$count++;}
        elseif($bulkAction==='delete'){$d->query("UPDATE users SET `status`='deleted' WHERE id=? AND role!='admin'",[$tid]);$count++;}
    }

    try{$pdo->prepare("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'bulk_'.$bulkAction,?,?,NOW())")->execute([$uid,'Bulk '.$bulkAction.' on '.$count.' users: '.implode(',',array_slice($ids,0,10)),$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    au_ok('Đã xử lý '.$count.' users',['affected'=>$count]);
}

// User companies list
if($action==='companies'){
    $companies=$d->fetchAll("SELECT shipping_company,COUNT(*) as count FROM users WHERE shipping_company IS NOT NULL AND shipping_company!='' AND `status`='active' GROUP BY shipping_company ORDER BY count DESC");
    au_ok('OK',$companies);
}

// User growth stats
if($action==='growth'){
    $days=min(intval($_GET['days']??30),365);
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    au_ok('OK',['daily'=>$daily,'total_active'=>$total]);
}

au_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
