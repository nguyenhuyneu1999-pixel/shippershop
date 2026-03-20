<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: text/plain");
$d = db();

// Check message count for user 2
$count = $d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id = 2 AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",[]);
echo "User 2 messages this month: " . $count['c'] . "\n";

$plan = getUserPlan(2);
echo "Plan: " . $plan['plan'] . "\n";
echo "Is Plus: " . ($plan['is_plus'] ? 'YES' : 'NO') . "\n";
echo "Message limit: " . $plan['limits']['messages_per_month'] . "\n";

$limitErr = checkLimit(2, 'messages_per_month');
echo "Limit check: " . ($limitErr ?: 'OK (within limit)') . "\n";

// Check all users message counts
$top = $d->fetchAll("SELECT sender_id, COUNT(*) c FROM messages WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY sender_id ORDER BY c DESC LIMIT 5");
echo "\nTop message senders this month:\n";
foreach($top as $t) echo "  User #{$t['sender_id']}: {$t['c']} messages\n";
