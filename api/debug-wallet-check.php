<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();

echo "=== Login Attempts ===\n";
$r = db()->fetchAll("SELECT ip, email, success, created_at FROM login_attempts ORDER BY created_at DESC LIMIT 5", []);
foreach ($r as $row) echo "{$row['ip']} | {$row['email']} | s={$row['success']} | {$row['created_at']}\n";
if (empty($r)) echo "(empty)\n";

echo "\n=== EXPLAIN Feed ===\n";
$exp = $pdo->query("EXPLAIN SELECT p.*, u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "type={$exp[0]['type']} key={$exp[0]['key']} rows={$exp[0]['rows']}\n";

echo "\n=== EXPLAIN Batch Likes ===\n";
$exp2 = $pdo->query("EXPLAIN SELECT post_id FROM likes WHERE user_id=2 AND post_id IN (1,2,3,4,5)")->fetchAll(PDO::FETCH_ASSOC);
echo "type={$exp2[0]['type']} key={$exp2[0]['key']} rows={$exp2[0]['rows']}\n";

echo "\n=== EXPLAIN Location Filter ===\n";
$exp3 = $pdo->query("EXPLAIN SELECT * FROM posts WHERE province='Ha Noi' AND status='active' ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "type={$exp3[0]['type']} key={$exp3[0]['key']} rows={$exp3[0]['rows']}\n";

echo "\n=== Index Count ===\n";
$idx = $pdo->query("SELECT TABLE_NAME, COUNT(DISTINCT INDEX_NAME) as cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_NAME ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($idx as $i) echo "{$i['TABLE_NAME']}: {$i['cnt']} indexes\n";
