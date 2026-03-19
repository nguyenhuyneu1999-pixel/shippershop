<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Create group avatars directory
$dir = __DIR__.'/../uploads/groups/';
if(!is_dir($dir)) mkdir($dir, 0755, true);

// Group config: id => [emoji, bg_color, text_color]
$groups = [
    1  => ['🚚', '#00b14f', '#fff', 'GHTK'],           // Shipper GHTK
    2  => ['🏍️', '#00b14f', '#fff', 'Grab'],           // Grab Be Gojek
    3  => ['🌆', '#EE4D2D', '#fff', 'SG'],              // Shipper Sài Gòn
    4  => ['🏛️', '#d32f2f', '#fff', 'HN'],              // Shipper Hà Nội
    5  => ['⭐', '#FF9800', '#fff', 'Review'],           // Review Đồ Ship
    6  => ['💬', '#9C27B0', '#fff', 'CF'],               // Confession
    7  => ['⛽', '#4CAF50', '#fff', 'Xăng'],            // Mẹo Tiết Kiệm Xăng
    8  => ['📦', '#d32f2f', '#fff', 'J&T'],              // J&T SPX Ninja Van
    9  => ['⚡', '#2196F3', '#fff', 'Tips'],             // Tips Giao Hàng Nhanh
    10 => ['❓', '#4CAF50', '#fff', 'Q&A'],              // Hỏi Đáp
    11 => ['🏖️', '#FF9800', '#fff', 'ĐN'],             // Shipper Đà Nẵng
    12 => ['🔧', '#9C27B0', '#fff', 'Gear'],             // Đồ Nghề
];

echo "Creating group avatar SVGs...\n";
foreach($groups as $id => $cfg) {
    $emoji = $cfg[0];
    $bg = $cfg[1];
    $svg = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">'
        . '<rect width="200" height="200" rx="100" fill="'.$bg.'"/>'
        . '<text x="100" y="115" text-anchor="middle" font-size="80" font-family="Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji">'.$emoji.'</text>'
        . '</svg>';
    
    $path = $dir . 'group_' . $id . '.svg';
    file_put_contents($path, $svg);
    
    $urlPath = '/uploads/groups/group_' . $id . '.svg';
    $d->query("UPDATE `groups` SET icon_image = ? WHERE id = ?", [$urlPath, $id]);
    echo "  Group $id: $urlPath\n";
}

// Also create PNG fallbacks using GD (more compatible)
if (extension_loaded('gd')) {
    echo "\nCreating PNG avatars with GD...\n";
    foreach($groups as $id => $cfg) {
        $bg = $cfg[1];
        $label = $cfg[3];
        
        // Parse hex color
        $r = hexdec(substr($bg,1,2));
        $g2 = hexdec(substr($bg,3,2));
        $b = hexdec(substr($bg,5,2));
        
        $img = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($img, $r, $g2, $b);
        $white = imagecolorallocate($img, 255, 255, 255);
        
        // Fill circle
        imagefilledrectangle($img, 0, 0, 200, 200, $bgColor);
        
        // Draw text (label)
        $fontSize = 5; // GD built-in font
        $tw = imagefontwidth($fontSize) * strlen($label);
        $th = imagefontheight($fontSize);
        $x = (200 - $tw) / 2;
        $y = (200 - $th) / 2;
        imagestring($img, $fontSize, $x, $y, $label, $white);
        
        $pngPath = $dir . 'group_' . $id . '.png';
        imagepng($img, $pngPath);
        imagedestroy($img);
        
        // Update DB to use PNG
        $urlPath = '/uploads/groups/group_' . $id . '.png';
        $d->query("UPDATE `groups` SET icon_image = ? WHERE id = ?", [$urlPath, $id]);
        echo "  Group $id: $urlPath (PNG)\n";
    }
} else {
    echo "GD not available, using SVG\n";
}

// Set cover images using seed images
echo "\nSetting cover images from seed photos...\n";
$covers = [
    1  => '/uploads/posts/seed_v2_1.jpg',
    2  => '/uploads/posts/seed_v2_3.jpg',
    3  => '/uploads/posts/seed_v2_5.jpg',
    4  => '/uploads/posts/seed_v2_7.jpg',
    5  => '/uploads/posts/seed_v2_9.jpg',
    6  => '/uploads/posts/seed_v2_11.jpg',
    7  => '/uploads/posts/seed_v2_13.jpg',
    8  => '/uploads/posts/seed_v2_15.jpg',
    9  => '/uploads/posts/seed_v2_17.jpg',
    10 => '/uploads/posts/seed_v2_19.jpg',
    11 => '/uploads/posts/seed_v2_21.jpg',
    12 => '/uploads/posts/seed_v2_23.jpg',
];
foreach($covers as $id => $path) {
    $fullPath = __DIR__ . '/..' . $path;
    if(file_exists($fullPath)) {
        $d->query("UPDATE `groups` SET cover_image = ? WHERE id = ?", [$path, $id]);
        echo "  Group $id cover: $path\n";
    } else {
        echo "  Group $id: MISSING $path\n";
    }
}

echo "\n=== VERIFY ===\n";
$groups = $d->fetchAll("SELECT id,name,icon_image,cover_image FROM `groups` ORDER BY id");
foreach($groups as $g) {
    echo "id={$g['id']} {$g['name']} icon={$g['icon_image']} cover={$g['cover_image']}\n";
}
echo "\nDONE\n";
