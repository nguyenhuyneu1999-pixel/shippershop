<?php
header('Content-Type: text/plain; charset=utf-8');
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();

echo "=== Avatar breakdown ===\n";
$stats = $pdo->query("SELECT 
    CASE 
        WHEN avatar LIKE '%randomuser.me%' THEN 'randomuser.me'
        WHEN avatar LIKE '/uploads/avatars/vn_%' THEN 'vn_new'
        WHEN avatar LIKE '/uploads/avatars/seed_%' THEN 'seed'
        WHEN avatar LIKE '/uploads/avatars/avatar_%' THEN 'real_upload'
        WHEN avatar IS NULL OR avatar = '' THEN 'null'
        ELSE 'other'
    END as type, COUNT(*) as cnt
    FROM users GROUP BY type ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($stats as $s) echo "  {$s['type']}: {$s['cnt']}\n";

echo "\n=== Sample seed users (still unchanged) ===\n";
$samples = $pdo->query("SELECT id, fullname, avatar FROM users WHERE avatar LIKE '%seed_%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples as $s) echo "  #{$s['id']} {$s['fullname']} → {$s['avatar']}\n";
if (empty($samples)) echo "  (none - all replaced!)\n";

echo "\n=== Sample vn_ users (new avatars) ===\n";
$samples2 = $pdo->query("SELECT id, fullname, avatar FROM users WHERE avatar LIKE '%vn_%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples2 as $s) echo "  #{$s['id']} {$s['fullname']} → {$s['avatar']}\n";

echo "\n=== Query that script uses ===\n";
$test = $pdo->query("SELECT id, fullname, avatar FROM users 
    WHERE (avatar LIKE '%randomuser.me%' OR avatar LIKE '%seed_%' OR avatar IS NULL OR avatar = '') 
    AND id > 1
    ORDER BY id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Matching: " . count($test) . "\n";
foreach ($test as $t) echo "  #{$t['id']} avatar={$t['avatar']}\n";
