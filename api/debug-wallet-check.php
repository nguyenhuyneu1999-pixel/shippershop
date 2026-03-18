<?php
set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';

$pdo = db()->getConnection();
$avatarDir = '/home/nhshiw2j/public_html/uploads/avatars/';

// Batch number from query string (run multiple times)
$batch = intval($_GET['batch'] ?? 1);
$perBatch = 15;
// Always offset 0 - processed users no longer match WHERE condition
$offset = 0;

// Vietnamese female name indicators
$femaleNames = ['Thị','Hương','Mai','Ngọc','Lan','Tuyết','Thảo','Kim','Linh','Hoa','Yến','Trang','Vy','Phương','Hạnh','Nhung','Dung','Quyên','Nhi','Trinh','Oanh','Thanh','Loan','Hiền','Mỹ','Uyên','Trâm','Châu','Hằng','Duyên','Anh','Huệ','Giang','Diệu','Bích','Thủy','Cúc','Sen','Thu','Xuân','Hà'];

function isFemale($name) {
    global $femaleNames;
    foreach ($femaleNames as $fn) {
        if (mb_strpos($name, $fn) !== false) return true;
    }
    return false;
}

// Get users needing avatar update (skip real uploads)
$users = $pdo->query("SELECT id, fullname, avatar FROM users 
    WHERE (avatar LIKE '%randomuser.me%' OR avatar LIKE '%seed_%' OR avatar IS NULL OR avatar = '') 
    AND id > 1
    ORDER BY id 
    LIMIT $perBatch OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "Batch $batch: No more users to process. DONE!\n";
    exit;
}

echo "Batch $batch: Processing " . count($users) . " users (offset $offset)\n\n";

$success = 0;
$fail = 0;
$ctx = stream_context_create(['http' => [
    'timeout' => 15, 
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'header' => "Accept: image/jpeg,image/png,*/*\r\n"
]]);

foreach ($users as $u) {
    $gender = isFemale($u['fullname']) ? 'female' : 'male';
    $url = "https://xsgames.co/randomusers/avatar.php?g=$gender";
    
    $data = @file_get_contents($url, false, $ctx);
    if (!$data || strlen($data) < 2000) {
        echo "  #{$u['id']} {$u['fullname']}: DOWNLOAD FAILED\n";
        $fail++;
        usleep(1000000); // 1s delay on failure
        continue;
    }
    
    $filename = "vn_" . $u['id'] . "_" . time() . ".jpg";
    $filepath = $avatarDir . $filename;
    
    // Resize to 200x200 with GD
    $srcImg = @imagecreatefromstring($data);
    if (!$srcImg) {
        echo "  #{$u['id']} {$u['fullname']}: INVALID IMAGE\n";
        $fail++;
        continue;
    }
    
    $dstImg = imagecreatetruecolor(200, 200);
    $srcW = imagesx($srcImg);
    $srcH = imagesy($srcImg);
    
    // Center crop to square then resize
    $cropSize = min($srcW, $srcH);
    $cropX = ($srcW - $cropSize) / 2;
    $cropY = ($srcH - $cropSize) / 2;
    
    imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX, $cropY, 200, 200, $cropSize, $cropSize);
    imagejpeg($dstImg, $filepath, 85);
    imagedestroy($srcImg);
    imagedestroy($dstImg);
    
    // Update DB
    $avatarUrl = '/uploads/avatars/' . $filename;
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $u['id']]);
    
    echo "  #{$u['id']} {$u['fullname']} ($gender): OK → $filename (" . filesize($filepath) . "b)\n";
    $success++;
    
    // Rate limit: 0.5s between downloads
    usleep(500000);
}

echo "\n=== Batch $batch Result ===\n";
echo "Success: $success, Failed: $fail\n";
echo "Next: ?batch=" . ($batch + 1) . "\n";

// Count remaining
$remaining = $pdo->query("SELECT COUNT(*) as c FROM users WHERE (avatar LIKE '%randomuser.me%' OR avatar LIKE '%seed_%' OR avatar IS NULL OR avatar = '') AND id > 1")->fetch()['c'];
echo "Remaining: $remaining users\n";
