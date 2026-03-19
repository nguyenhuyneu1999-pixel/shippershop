<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$tables=['users','posts','comments','likes','groups','group_posts','marketplace_listings',
'conversations','messages','traffic_alerts','wallets','wallet_transactions','push_subscriptions',
'referrals','notification_reads','follows','friends'];
foreach($tables as $t){
    try{$c=$d->fetchOne("SELECT COUNT(*) as c FROM `$t`");echo "$t: ".$c['c']."\n";}
    catch(Throwable $e){echo "$t: N/A\n";}
}
echo "\nAdmin user: ";
$admin=$d->fetchOne("SELECT id,fullname,username FROM users WHERE id=2");
print_r($admin);
