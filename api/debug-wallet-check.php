<?php
set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';

$dir = '/home/nhshiw2j/public_html/uploads/avatars/';

// Check seed file quality
echo "=== Seed file quality ===\n";
for ($i = 100; $i <= 105; $i++) {
    $f = $dir . "seed_$i.jpg";
    if (file_exists($f)) {
        $info = @getimagesize($f);
        $sz = filesize($f);
        echo "seed_$i.jpg: {$sz}b";
        if ($info) echo " {$info[0]}x{$info[1]} {$info['mime']}";
        else echo " NOT VALID IMAGE";
        echo "\n";
    }
}

// Test xsgames.co - download 5 samples to check quality
echo "\n=== xsgames.co Asian-looking test ===\n";
$ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0']]);
for ($i = 0; $i < 3; $i++) {
    $g = $i % 2 === 0 ? 'male' : 'female';
    $data = @file_get_contents("https://xsgames.co/randomusers/avatar.php?g=$g", false, $ctx);
    if ($data) {
        $testFile = $dir . "test_xsg_$i.jpg";
        file_put_contents($testFile, $data);
        $info = getimagesize($testFile);
        echo "xsg_$i ($g): " . strlen($data) . "b {$info[0]}x{$info[1]}\n";
        unlink($testFile);
    }
    usleep(500000); // 0.5s delay
}

// Test thispersondoesnotexist alternative for Asian faces
echo "\n=== fakeface.rest test ===\n";
$data = @file_get_contents("https://fakeface.rest/face/json?minimum_age=20&maximum_age=40", false, $ctx);
if ($data) {
    $json = json_decode($data, true);
    if ($json && isset($json['image_url'])) {
        echo "fakeface.rest: " . $json['image_url'] . "\n";
        echo "Age: " . ($json['age'] ?? '?') . ", Gender: " . ($json['gender'] ?? '?') . "\n";
        $imgData = @file_get_contents($json['image_url'], false, $ctx);
        echo "Image: " . ($imgData ? strlen($imgData) . " bytes" : "FAILED") . "\n";
    } else {
        echo "fakeface.rest: invalid response\n";
    }
} else {
    echo "fakeface.rest: FAILED\n";
}

// Test generated.photos free tier
echo "\n=== boringavatars test ===\n";
$boring = @file_get_contents("https://source.boringavatars.com/beam/200/Nguyen%20Van%20Huy?colors=EE4D2D,FF6600,00b14f,2196F3,9C27B0", false, $ctx);
if ($boring) echo "boringavatars: " . strlen($boring) . "b (SVG)\n";
else echo "boringavatars: FAILED\n";

// Show what we need to do
$pdo = db()->getConnection();
echo "\n=== Plan ===\n";
echo "Users with randomuser.me: 15 → REPLACE\n";
echo "Users with seed files: 694 → CHECK QUALITY, replace if low\n";
echo "Users with null avatar: 4 → CREATE\n";
echo "Users with real uploads: 3 → KEEP\n";

// Check if seed files look like real photos or generated initials
$firstSeed = $dir . "seed_100.jpg";
if (file_exists($firstSeed)) {
    $img = @imagecreatefromjpeg($firstSeed);
    if ($img) {
        $w = imagesx($img);
        $h = imagesy($img);
        // Sample colors from center and edges to determine if gradient/solid or photo
        $centerColor = imagecolorat($img, $w/2, $h/2);
        $cornerColor = imagecolorat($img, 5, 5);
        $cr = ($centerColor >> 16) & 0xFF;
        $cg = ($centerColor >> 8) & 0xFF;
        $cb = $centerColor & 0xFF;
        $er = ($cornerColor >> 16) & 0xFF;
        $eg = ($cornerColor >> 8) & 0xFF;
        $eb = $cornerColor & 0xFF;
        echo "seed_100 center: rgb($cr,$cg,$cb), corner: rgb($er,$eg,$eb)\n";
        echo "Looks like: " . (abs($cr-$er) + abs($cg-$eg) + abs($cb-$eb) < 50 ? "SOLID/GRADIENT (need replace)" : "PHOTO (might keep)") . "\n";
        imagedestroy($img);
    }
}
