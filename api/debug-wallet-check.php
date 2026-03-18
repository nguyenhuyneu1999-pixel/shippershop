<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

// Check existing images in uploads/posts
echo "=== Images in uploads/posts ===\n";
$dir = '/home/nhshiw2j/public_html/uploads/posts/';
if (is_dir($dir)) {
    $files = scandir($dir);
    $images = array_filter($files, function($f) { return preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f); });
    echo "Total images: " . count($images) . "\n";
    $sample = array_slice(array_values($images), 0, 30);
    foreach ($sample as $f) echo "  /uploads/posts/$f (" . round(filesize($dir.$f)/1024) . "KB)\n";
} else { echo "Dir not found\n"; }

// Check existing post images format
echo "\n=== Sample images in posts ===\n";
$pdo = db()->getConnection();
$imgs = $pdo->query("SELECT id, images FROM posts WHERE images IS NOT NULL AND images != '' AND images != '[]' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($imgs as $i) echo "  post#{$i['id']}: {$i['images']}\n";

// Check user avatars
echo "\n=== User avatars sample ===\n";
$avs = $pdo->query("SELECT id, fullname, avatar FROM users WHERE avatar IS NOT NULL AND avatar != '' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($avs as $a) echo "  #{$a['id']} {$a['fullname']}: {$a['avatar']}\n";

// Check avatars directory
echo "\n=== Avatars directory ===\n";
$adir = '/home/nhshiw2j/public_html/uploads/avatars/';
if (is_dir($adir)) {
    $afiles = scandir($adir);
    $avatars = array_filter($afiles, function($f) { return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); });
    echo "Total avatars: " . count($avatars) . "\n";
    foreach (array_slice(array_values($avatars), 0, 5) as $f) echo "  /uploads/avatars/$f\n";
}
