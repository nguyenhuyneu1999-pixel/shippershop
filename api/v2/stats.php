<?php
// ShipperShop API v2 — Public Stats (cached, no auth)
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

$stats = cache_remember('public_stats', function() {
    $d = db();
    return [
        'users' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']),
        'posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']) + intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE `status`='active'")['c']),
        'groups' => intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']),
        'comments' => intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE `status`='active'")['c']),
        'marketplace' => intval($d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings WHERE `status`='active'")['c']),
        'online' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),
        'today_posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE DATE(created_at)=CURDATE()")['c']),
        'companies' => ['GHTK','GHN','J&T','SPX','Viettel Post','Ninja Van','BEST','Ahamove','Grab Express','Be','Gojek']
    ];
}, 300);

echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
