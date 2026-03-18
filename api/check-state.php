<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

$dir = '/home/nhshiw2j/public_html/uploads/posts/';
$files = array_filter(scandir($dir), function($f){ return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); });
$images = array_values($files);
echo "Total: " . count($images) . "\n";
foreach ($images as $f) echo "$f\n";
