<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();

echo "=== Users with avatars ===\n";
$users = $pdo->query("SELECT id, fullname, avatar, shipping_company FROM users ORDER BY id LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "#{$u['id']} {$u['fullname']} | avatar=" . ($u['avatar'] ?: '(null)') . " | {$u['shipping_company']}\n";
}

echo "\n=== Avatar stats ===\n";
$total = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$hasAv = $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar IS NOT NULL AND avatar != ''")->fetch()['c'];
$nullAv = $total - $hasAv;
echo "Total users: $total\n";
echo "Has avatar: $hasAv\n";
echo "No avatar: $nullAv\n";

echo "\n=== Sample avatar URLs ===\n";
$samples = $pdo->query("SELECT avatar FROM users WHERE avatar IS NOT NULL AND avatar != '' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples as $s) echo "  {$s['avatar']}\n";

echo "\n=== Upload dir ===\n";
$dir = '/home/nhshiw2j/public_html/uploads/avatars/';
if (is_dir($dir)) {
    $files = scandir($dir);
    $count = count($files) - 2;
    echo "Files in avatars/: $count\n";
    foreach (array_slice($files, 2, 5) as $f) echo "  $f\n";
} else { echo "Dir not found\n"; }
