<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== CURRENT PLANS ===\n";
$plans=$d->fetchAll("SELECT * FROM subscription_plans ORDER BY id");
foreach($plans as $p){
    echo "id={$p['id']} name={$p['name']} slug={$p['slug']} price={$p['price']} duration={$p['duration_days']} badge={$p['badge']} active={$p['is_active']} sort={$p['sort_order']}\n";
}

echo "\n=== CURRENT SUBSCRIPTIONS ===\n";
$subs=$d->fetchAll("SELECT us.*, sp.name as plan_name FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.`status`='active'");
foreach($subs as $s){
    echo "user={$s['user_id']} plan={$s['plan_name']} expires={$s['expires_at']}\n";
}

echo "\n=== COLUMNS in subscription_plans ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM subscription_plans");
foreach($cols as $c) echo $c['Field'].", ";
echo "\n";
