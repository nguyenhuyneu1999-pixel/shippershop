<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$r = db()->fetchAll("SELECT ip, email, success, created_at FROM login_attempts ORDER BY created_at DESC LIMIT 5", []);
foreach ($r as $row) echo "{$row['ip']} | {$row['email']} | success={$row['success']} | {$row['created_at']}\n";
if (empty($r)) echo "(no records)\n";

// Also verify EXPLAIN with new indexes
$pdo = db()->getConnection();
$exp = $pdo->query("EXPLAIN SELECT p.*, u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "\nEXPLAIN feed: type={$exp[0]['type']} key={$exp[0]['key']} rows={$exp[0]['rows']}\n";

$exp2 = $pdo->query("EXPLAIN SELECT post_id FROM likes WHERE user_id=2 AND post_id IN (1,2,3,4,5)")->fetchAll(PDO::FETCH_ASSOC);
echo "EXPLAIN batch likes: type={$exp2[0]['type']} key={$exp2[0]['key']} rows={$exp2[0]['rows']}\n";
