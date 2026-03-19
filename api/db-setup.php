<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== DB HEALTH CHECK ===\n";

// Check all tables exist
$tables=['users','posts','comments','likes','post_likes','comment_likes','saved_posts','follows',
'notifications','notification_reads','groups','group_members','group_posts',
'friends','conversations','messages','wallets','wallet_transactions',
'marketplace_listings','subscription_plans','user_subscriptions',
'csrf_tokens','rate_limits','audit_log','traffic_alerts','traffic_confirms','traffic_comments',
'push_subscriptions','payos_payments','map_pins','hashtags'];

foreach($tables as $t){
    try{$c=$d->fetchOne("SELECT COUNT(*) as c FROM `$t`")['c'];echo "  ✅ $t: $c rows\n";}
    catch(Throwable $e){echo "  ❌ $t: ".$e->getMessage()."\n";}
}

echo "\n=== ORPHAN DATA ===\n";
// Posts by deleted users
$orphanPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE u.id IS NULL")['c'];
echo "Posts by non-existent users: $orphanPosts\n";

// Comments by deleted users 
$orphanCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments c LEFT JOIN users u ON c.user_id=u.id WHERE u.id IS NULL")['c'];
echo "Comments by non-existent users: $orphanCmts\n";

// Double-path images
$dblImg=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE images LIKE '%/uploads/posts//uploads/%'")['c'];
echo "Posts with double-path images: $dblImg\n";

// Expired subscriptions still active
$expSub=$d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active' AND expires_at < NOW()")['c'];
echo "Expired but still 'active' subscriptions: $expSub\n";

// Pending payos payments older than 1 hour
$stalePay=$d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status`='pending' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c'];
echo "Stale pending payOS payments (>1h): $stalePay\n";

echo "\nDONE\n";
