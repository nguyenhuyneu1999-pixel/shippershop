<?php
// ShipperShop API v2 — Platform Statistics (Public)
// Overall platform metrics for landing page, investors, about
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

$d=db();

try {

$data=cache_remember('platform_stats_public', function() use($d) {
    return [
        'users'=>['total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']),'this_week'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']),'this_month'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'])],
        'posts'=>['total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']),'this_week'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'])],
        'deliveries'=>['total'=>intval($d->fetchOne("SELECT COALESCE(SUM(total_success),0) as s FROM users")['s'])],
        'comments'=>['total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c'])],
        'groups'=>['total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c'])],
        'provinces_covered'=>intval($d->fetchOne("SELECT COUNT(DISTINCT province) as c FROM posts WHERE province IS NOT NULL AND province!=''")['c']),
        'shipping_companies'=>intval($d->fetchOne("SELECT COUNT(DISTINCT shipping_company) as c FROM users WHERE shipping_company IS NOT NULL AND shipping_company!=''")['c']),
        'online_now'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),
        'generated_at'=>date('c'),
    ];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
