<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();

echo "Posts active: " . $pdo->query("SELECT COUNT(*) as c FROM posts WHERE status='active'")->fetch(PDO::FETCH_ASSOC)['c'] . "\n";
echo "Max ID: " . $pdo->query("SELECT MAX(id) as m FROM posts")->fetch(PDO::FETCH_ASSOC)['m'] . "\n";

echo "\nImages in uploads/posts:\n";
$dir = '/home/nhshiw2j/public_html/uploads/posts/';
$files = is_dir($dir) ? array_filter(scandir($dir), function($f){ return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); }) : [];
echo "  Count: " . count($files) . "\n";
$sample = array_slice(array_values($files), 0, 20);
foreach ($sample as $f) echo "  $f\n";

echo "\nAvatars:\n";
$adir = '/home/nhshiw2j/public_html/uploads/avatars/';
$afiles = is_dir($adir) ? array_filter(scandir($adir), function($f){ return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); }) : [];
echo "  Count: " . count($afiles) . "\n";

echo "\nUsers with avatars: " . $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar IS NOT NULL AND avatar != ''")->fetch(PDO::FETCH_ASSOC)['c'] . "\n";
echo "Users 3-20 shipping:\n";
$us = $pdo->query("SELECT id,fullname,shipping_company,avatar FROM users WHERE id BETWEEN 3 AND 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($us as $u) echo "  #{$u['id']} {$u['fullname']} ({$u['shipping_company']}) av=" . ($u['avatar'] ? substr($u['avatar'],0,40) : 'null') . "\n";

echo "\nPost images sample:\n";
$imgs = $pdo->query("SELECT DISTINCT images FROM posts WHERE images IS NOT NULL AND images != '' AND images != '[]' LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
foreach ($imgs as $i) echo "  {$i['images']}\n";
