<?php
set_time_limit(120);
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';

$pdo = db()->getConnection();
$avatarDir = '/home/nhshiw2j/public_html/uploads/avatars/';
if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

// Check GD
echo "GD: " . (extension_loaded('gd') ? 'YES' : 'NO') . "\n";
echo "GD info: " . (function_exists('gd_info') ? json_encode(array_keys(gd_info())) : 'N/A') . "\n";

// Test downloading from external sources
$testUrls = [
    'xsgames_male' => 'https://xsgames.co/randomusers/avatar.php?g=male',
    'xsgames_female' => 'https://xsgames.co/randomusers/avatar.php?g=female',
    'pravatar' => 'https://i.pravatar.cc/200?img=1',
    'uifaces' => 'https://randomuser.me/api/portraits/men/1.jpg',
];

foreach ($testUrls as $name => $url) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 1000) {
        echo "$name: OK (" . strlen($data) . " bytes)\n";
        // Save test
        $testFile = $avatarDir . "test_" . $name . ".jpg";
        file_put_contents($testFile, $data);
        // Check if valid image
        $info = @getimagesize($testFile);
        echo "  Image: " . ($info ? "{$info[0]}x{$info[1]} {$info['mime']}" : "INVALID") . "\n";
        @unlink($testFile);
    } else {
        echo "$name: FAILED\n";
    }
}

// Test GD resize
echo "\n=== GD Resize Test ===\n";
$testImg = @imagecreatetruecolor(200, 200);
if ($testImg) {
    $color = imagecolorallocate($testImg, 238, 77, 45);
    imagefill($testImg, 0, 0, $color);
    $testPath = $avatarDir . "test_gd.jpg";
    imagejpeg($testImg, $testPath, 85);
    imagedestroy($testImg);
    echo "GD create: OK (" . filesize($testPath) . " bytes)\n";
    @unlink($testPath);
} else {
    echo "GD create: FAILED\n";
}

// Count users needing avatars
$total = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$western = $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar LIKE '%randomuser.me%'")->fetch()['c'];
$seed = $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar LIKE '%seed_%'")->fetch()['c'];
$nullAv = $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar IS NULL OR avatar = ''")->fetch()['c'];
$local = $pdo->query("SELECT COUNT(*) as c FROM users WHERE avatar LIKE '/uploads/avatars/avatar_%'")->fetch()['c'];

echo "\n=== Avatar Stats ===\n";
echo "Total users: $total\n";
echo "Western (randomuser.me): $western (need replace)\n";
echo "Seed files: $seed (need check)\n";
echo "No avatar: $nullAv (need create)\n";
echo "Real local uploads: $local (keep)\n";
echo "Need to fix: " . ($western + $nullAv) . "\n";

// Check if seed files exist
echo "\n=== Seed file check ===\n";
$seedUsers = $pdo->query("SELECT id, avatar FROM users WHERE avatar LIKE '%seed_%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($seedUsers as $u) {
    $path = '/home/nhshiw2j/public_html' . $u['avatar'];
    echo "  #{$u['id']} {$u['avatar']} → " . (file_exists($path) ? "EXISTS (" . filesize($path) . "b)" : "MISSING") . "\n";
}
