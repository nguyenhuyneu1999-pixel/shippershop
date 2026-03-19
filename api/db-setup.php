<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
header('Content-Type: text/plain');

echo "=== TEST getUserPlan ===\n";
// Test with user 2 (admin, should be free unless subscribed)
$plan2 = getUserPlan(2);
echo "User 2: plan={$plan2['plan']} is_plus=" . ($plan2['is_plus'] ? 'true' : 'false') . "\n";
echo "Limits: " . json_encode($plan2['limits']) . "\n";

echo "\n=== TEST getUserUsage ===\n";
$usage2 = getUserUsage(2);
echo "User 2 usage: " . json_encode($usage2) . "\n";

echo "\n=== TEST checkLimit ===\n";
$tests = ['posts_per_day', 'messages_per_month', 'groups_max', 'marketplace_max'];
foreach ($tests as $t) {
    $err = checkLimit(2, $t);
    echo "$t: " . ($err ?: 'OK (within limit)') . "\n";
}

echo "\n=== SUBSCRIPTION STATE ===\n";
$sub = db()->fetchOne("SELECT us.*, sp.name FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=2 AND us.`status`='active' ORDER BY us.expires_at DESC LIMIT 1");
if ($sub) {
    echo "Active sub: {$sub['name']}, expires: {$sub['expires_at']}\n";
} else {
    echo "No active subscription\n";
}

echo "\nDONE\n";
