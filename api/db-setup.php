<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

$tables = ['users','posts','comments','likes','post_likes','groups','group_posts',
    'marketplace_listings','traffic_alerts','messages','conversations',
    'wallets','wallet_transactions','user_subscriptions','push_subscriptions'];

foreach($tables as $t) {
    try {
        $c = $d->fetchOne("SELECT COUNT(*) as c FROM `$t`")['c'];
        echo "✅ $t: $c rows\n";
    } catch(Throwable $e) {
        echo "❌ $t: " . $e->getMessage() . "\n";
    }
}
