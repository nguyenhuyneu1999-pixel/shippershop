<?php
// ShipperShop API v2 — Admin Data Export
// Export users, posts, transactions as CSV/JSON for admin
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ae_ok($msg,$data=null){header('Content-Type: application/json; charset=utf-8');echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ae_fail($msg,$code=400){header('Content-Type: application/json');http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ae_fail('Admin only',403);

$format=$_GET['format']??'json'; // json or csv

// Export users
if($action==='users'){
    $users=$d->fetchAll("SELECT id,fullname,email,phone,shipping_company,`status`,is_verified,total_posts,total_success,created_at FROM users ORDER BY id");
    if($format==='csv'){
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_'.date('Y-m-d').'.csv"');
        echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8
        echo "ID,Name,Email,Phone,Company,Status,Verified,Posts,Deliveries,Created\n";
        foreach($users as $u){
            echo implode(',',[$u['id'],'"'.str_replace('"','""',$u['fullname']).'"',$u['email'],$u['phone']??'',$u['shipping_company']??'',$u['status'],$u['is_verified'],$u['total_posts'],$u['total_success'],$u['created_at']])."\n";
        }
        exit;
    }
    ae_ok('OK',['users'=>$users,'count'=>count($users)]);
}

// Export transactions
if($action==='transactions'){
    $from=$_GET['from']??date('Y-m-d',strtotime('-30 days'));
    $to=$_GET['to']??date('Y-m-d');
    $txns=$d->fetchAll("SELECT wt.*,u.fullname FROM wallet_transactions wt LEFT JOIN users u ON wt.user_id=u.id WHERE wt.created_at BETWEEN ? AND ? ORDER BY wt.created_at DESC",[$from.' 00:00:00',$to.' 23:59:59']);
    if($format==='csv'){
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_'.$from.'_'.$to.'.csv"');
        echo "\xEF\xBB\xBF";
        echo "ID,User ID,User Name,Type,Amount,Description,Created\n";
        foreach($txns as $t){
            echo implode(',',[$t['id'],$t['user_id'],'"'.str_replace('"','""',$t['fullname']??'').'"',$t['type'],$t['amount'],'"'.str_replace('"','""',$t['description']??'').'"',$t['created_at']])."\n";
        }
        exit;
    }
    ae_ok('OK',['transactions'=>$txns,'count'=>count($txns),'from'=>$from,'to'=>$to]);
}

// Export posts summary
if($action==='posts'){
    $days=min(intval($_GET['days']??30),365);
    $posts=$d->fetchAll("SELECT p.id,p.user_id,u.fullname,p.type,p.likes_count,p.comments_count,p.province,p.district,p.`status`,p.created_at FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) ORDER BY p.created_at DESC");
    if($format==='csv'){
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="posts_'.date('Y-m-d').'.csv"');
        echo "\xEF\xBB\xBF";
        echo "ID,User ID,Author,Type,Likes,Comments,Province,District,Status,Created\n";
        foreach($posts as $p){
            echo implode(',',[$p['id'],$p['user_id'],'"'.str_replace('"','""',$p['fullname']??'').'"',$p['type']??'post',$p['likes_count'],$p['comments_count'],$p['province']??'',$p['district']??'',$p['status'],$p['created_at']])."\n";
        }
        exit;
    }
    ae_ok('OK',['posts'=>$posts,'count'=>count($posts),'days'=>$days]);
}

// Export overview (summary stats)
if(!$action||$action==='overview'){
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
    $monthlyActive=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    ae_ok('OK',[
        'total_users'=>$totalUsers,
        'total_posts'=>$totalPosts,
        'total_revenue'=>$totalRevenue,
        'monthly_active'=>$monthlyActive,
        'export_options'=>['users','transactions','posts'],
        'formats'=>['json','csv'],
    ]);
}

ae_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
