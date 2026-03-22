<?php
// ShipperShop API v2 — Delivery Stats
// Shipper delivery statistics, monthly summary, company breakdown
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

$d=db();$action=$_GET['action']??'';

function ds_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}

// Overall delivery stats
if(!$action||$action==='summary'){
    $data=cache_remember('delivery_stats_'.$userId, function() use($d,$userId) {
        $u=$d->fetchOne("SELECT total_posts,total_success,shipping_company,created_at FROM users WHERE id=?",[$userId]);
        if(!$u) return [];

        $days=max(1,floor((time()-strtotime($u['created_at']))/86400));
        $avgPerDay=round(intval($u['total_success'])/$days,1);

        // Monthly breakdown (last 6 months)
        $monthly=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month,COUNT(*) as posts FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month",[$userId]);

        // Province breakdown
        $provinces=$d->fetchAll("SELECT province,COUNT(*) as count FROM posts WHERE user_id=? AND `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY count DESC LIMIT 10",[$userId]);

        return [
            'total_deliveries'=>intval($u['total_success']),
            'total_posts'=>intval($u['total_posts']),
            'company'=>$u['shipping_company'],
            'days_active'=>$days,
            'avg_per_day'=>$avgPerDay,
            'monthly'=>$monthly,
            'provinces'=>$provinces,
        ];
    }, 300);

    ds_ok('OK',$data);
}

// Platform-wide delivery stats (public)
if($action==='platform'){
    $data=cache_remember('platform_delivery_stats', function() use($d) {
        $totalDeliveries=intval($d->fetchOne("SELECT COALESCE(SUM(total_success),0) as s FROM users")['s']);
        $totalShippers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE total_success>0 AND `status`='active'")['c']);
        $topCompanies=$d->fetchAll("SELECT shipping_company,COUNT(*) as shippers,SUM(total_success) as deliveries FROM users WHERE shipping_company IS NOT NULL AND shipping_company!='' AND `status`='active' GROUP BY shipping_company ORDER BY deliveries DESC LIMIT 10");
        $topProvinces=$d->fetchAll("SELECT province,COUNT(*) as posts FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY posts DESC LIMIT 10");

        return ['total_deliveries'=>$totalDeliveries,'total_shippers'=>$totalShippers,'top_companies'=>$topCompanies,'top_provinces'=>$topProvinces];
    }, 600);
    ds_ok('OK',$data);
}

ds_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
