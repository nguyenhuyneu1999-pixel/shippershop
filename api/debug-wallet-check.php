<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain; charset=utf-8');

echo "=== Posts table structure ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) default={$c['Default']}\n";

echo "\n=== Sample post ===\n";
$p = $pdo->query("SELECT * FROM posts WHERE status='active' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
foreach ($p as $k => $v) echo "  $k: " . substr($v ?? 'NULL', 0, 100) . "\n";

echo "\n=== Current post count ===\n";
$cnt = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
echo "  Active: {$cnt['c']}\n";

echo "\n=== Max post ID ===\n";
$max = $pdo->query("SELECT MAX(id) as m FROM posts")->fetch(PDO::FETCH_ASSOC);
echo "  Max ID: {$max['m']}\n";

echo "\n=== Users sample (for avatar/names) ===\n";
$users = $pdo->query("SELECT id, fullname, avatar, shipping_company FROM users WHERE status='active' ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) echo "  #{$u['id']} {$u['fullname']} ({$u['shipping_company']}) avatar=" . ($u['avatar'] ? 'YES' : 'NO') . "\n";

echo "\n=== Users count ===\n";
$uc = $pdo->query("SELECT COUNT(*) as c FROM users WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
echo "  Active users: {$uc['c']}\n";

echo "\n=== Provinces used ===\n";
$provs = $pdo->query("SELECT DISTINCT province FROM posts WHERE province IS NOT NULL AND province != '' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($provs as $p) echo "  {$p['province']}\n";

echo "\n=== Post types ===\n";
$types = $pdo->query("SELECT post_type, COUNT(*) as c FROM posts GROUP BY post_type")->fetchAll(PDO::FETCH_ASSOC);
foreach ($types as $t) echo "  {$t['post_type']}: {$t['c']}\n";
