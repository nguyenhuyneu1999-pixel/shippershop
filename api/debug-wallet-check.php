<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();

// Check indexes on key tables
$tables = ['posts','comments','messages','notifications','users','wallets','wallet_transactions','conversations','follows','friends','traffic_alerts','marketplace_listings','post_likes','comment_likes'];
foreach ($tables as $t) {
    try {
        $idx = $pdo->query("SHOW INDEX FROM $t")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_unique(array_column($idx, 'Key_name'));
        echo "$t: " . implode(', ', $names) . "\n";
    } catch(Exception $e) { echo "$t: ERROR\n"; }
}

echo "\n=== Table sizes ===\n";
$rows = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_ROWS DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo sprintf("%-30s %6s rows  %8s KB\n", $r['TABLE_NAME'], $r['TABLE_ROWS'], round($r['DATA_LENGTH']/1024));
}

echo "\n=== Slow query candidates ===\n";
// Check posts query without index
try {
    $pdo->query("EXPLAIN SELECT * FROM posts ORDER BY created_at DESC LIMIT 20")->fetchAll();
    $exp = $pdo->query("EXPLAIN SELECT p.*, u.fullname, u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.province='Ha Noi' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exp as $e) echo "posts+filter: type={$e['type']} rows={$e['rows']} key={$e['key']}\n";
} catch(Exception $e) { echo "EXPLAIN error: " . $e->getMessage() . "\n"; }

// Check messages query
try {
    $exp = $pdo->query("EXPLAIN SELECT * FROM messages WHERE conversation_id=1 ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exp as $e) echo "messages: type={$e['type']} rows={$e['rows']} key={$e['key']}\n";
} catch(Exception $e) { echo "messages EXPLAIN: " . $e->getMessage() . "\n"; }

// Check notifications
try {
    $exp = $pdo->query("EXPLAIN SELECT * FROM notifications WHERE user_id=2 ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($exp as $e) echo "notifications: type={$e['type']} rows={$e['rows']} key={$e['key']}\n";
} catch(Exception $e) {}
