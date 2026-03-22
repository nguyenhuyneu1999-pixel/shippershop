<?php
// ShipperShop API v2 — User Summary Card
// Quick summary card data for hover/popup previews
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){echo json_encode(['success'=>true,'data'=>null]);exit;}

$data=cache_remember('summary_card_'.$userId, function() use($d,$userId) {
    $u=$d->fetchOne("SELECT id,fullname,avatar,bio,shipping_company,total_posts,total_success,is_verified,is_online,created_at FROM users WHERE id=? AND `status`='active'",[$userId]);
    if(!$u) return null;
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$userId])['s']);
    $badges=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_badges WHERE user_id=?",[$userId])['c']);
    $level=max(1,floor($xp/100)+1);
    $days=max(1,floor((time()-strtotime($u['created_at']))/86400));

    return ['id'=>intval($u['id']),'fullname'=>$u['fullname'],'avatar'=>$u['avatar'],'bio'=>$u['bio'],'company'=>$u['shipping_company'],'verified'=>intval($u['is_verified']),'online'=>intval($u['is_online']),'posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'followers'=>$followers,'level'=>$level,'xp'=>$xp,'badges'=>$badges,'days'=>$days];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
