<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();

try {
    $r = db()->fetchAll("SELECT ip, email, success, created_at FROM login_attempts ORDER BY created_at DESC LIMIT 3", []);
    echo "Login attempts: " . count($r) . "\n";
    foreach ($r as $row) echo "  {$row['ip']} | {$row['email']} | s={$row['success']}\n";
} catch (Throwable $e) { echo "Login attempts ERR: " . $e->getMessage() . "\n"; }

try {
    $exp = $pdo->query("EXPLAIN SELECT p.id FROM posts p WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nFeed EXPLAIN: type={$exp[0]['type']} key={$exp[0]['key']} rows={$exp[0]['rows']}\n";
} catch (Throwable $e) { echo "Feed EXPLAIN ERR: " . $e->getMessage() . "\n"; }

try {
    $exp2 = $pdo->query("EXPLAIN SELECT post_id FROM likes WHERE user_id=2 AND post_id IN (1,2,3)")->fetchAll(PDO::FETCH_ASSOC);
    echo "Likes batch: type={$exp2[0]['type']} key={$exp2[0]['key']} rows={$exp2[0]['rows']}\n";
} catch (Throwable $e) { echo "Likes EXPLAIN ERR: " . $e->getMessage() . "\n"; }

try {
    $exp3 = $pdo->query("EXPLAIN SELECT id FROM posts WHERE province='HN' AND status='active' ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    echo "Location: type={$exp3[0]['type']} key={$exp3[0]['key']} rows={$exp3[0]['rows']}\n";
} catch (Throwable $e) { echo "Location ERR: " . $e->getMessage() . "\n"; }

try {
    $cnt = $pdo->query("SELECT COUNT(DISTINCT INDEX_NAME) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()")->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal indexes: {$cnt['c']}\n";
} catch (Throwable $e) { echo "Index count ERR: " . $e->getMessage() . "\n"; }

echo "\nDONE\n";
