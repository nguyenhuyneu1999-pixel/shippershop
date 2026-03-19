<?php
$dir = '/home/nhshiw2j/public_html/uploads/posts/real';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$themes = [
    ['Tips Tiết Kiệm Xăng', '#00b14f', '⛽'],
    ['Thu Nhập Shipper 2026', '#7C3AED', '💰'],
    ['So Sánh Hãng Ship', '#EE4D2D', '📊'],
    ['Cảnh Báo Lừa Đảo', '#d32f2f', '⚠️'],
    ['Mẹo Giao Hàng Nhanh', '#ff6600', '🚀'],
    ['Checklist Shipper Mới', '#2196F3', '✅'],
    ['Mùa Mưa An Toàn', '#0288d1', '🌧️'],
    ['Chuyện Vui Shipper', '#ff9800', '😂'],
    ['Review GHTK', '#00b14f', '🟢'],
    ['Review GHN', '#ff6600', '🟠'],
    ['Review J&T', '#d32f2f', '🔴'],
    ['Review SPX', '#EE4D2D', '📦'],
    ['Tips Khu Q7 HCM', '#9C27B0', '📍'],
    ['Tips Khu Cầu Giấy HN', '#3F51B5', '📍'],
    ['Phụ Phí Xăng Dầu', '#795548', '📰'],
    ['TMDT 2026', '#00bcd4', '📈'],
    ['Tâm Sự Shipper', '#E91E63', '💬'],
    ['Đời Ship 3 Năm', '#4CAF50', '💪'],
    ['Bảo Hiểm Shipper', '#FF5722', '🛡️'],
    ['Sửa Xe Cơ Bản', '#607D8B', '🔧'],
];

$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
if (!file_exists($font)) $font = false;

foreach ($themes as $i => $t) {
    $file = $dir . '/real_' . ($i+1) . '.jpg';
    if (file_exists($file)) continue;
    
    $w = 800; $h = 600;
    $img = imagecreatetruecolor($w, $h);
    
    // Parse hex color
    $hex = $t[1];
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    
    // Gradient background
    for ($y = 0; $y < $h; $y++) {
        $factor = $y / $h;
        $cr = (int)($r * (1 - $factor * 0.5));
        $cg = (int)($g * (1 - $factor * 0.5));
        $cb = (int)($b * (1 - $factor * 0.5));
        $c = imagecolorallocate($img, max(0,min(255,$cr)), max(0,min(255,$cg)), max(0,min(255,$cb)));
        imageline($img, 0, $y, $w, $y, $c);
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $light = imagecolorallocate($img, 255, 255, 255);
    
    // Add pattern overlay
    for ($px = 0; $px < $w; $px += 40) {
        for ($py = 0; $py < $h; $py += 40) {
            $semi = imagecolorallocatealpha($img, 255, 255, 255, 120);
            imagefilledellipse($img, $px, $py, 3, 3, $semi);
        }
    }
    
    if ($font) {
        // Logo
        imagettftext($img, 14, 0, 30, 50, $light, $font, 'SHIPPERSHOP.VN');
        
        // Title
        imagettftext($img, 32, 0, 30, 300, $white, $font, $t[0]);
        
        // Subtitle
        imagettftext($img, 16, 0, 30, 350, $light, $font, 'Cong dong Shipper Viet Nam');
        
        // Bottom
        imagettftext($img, 12, 0, 30, 560, $light, $font, 'shippershop.vn | #shipper #giaohang');
    } else {
        imagestring($img, 5, 30, 250, $t[0], $white);
        imagestring($img, 4, 30, 290, 'SHIPPERSHOP.VN', $light);
    }
    
    imagejpeg($img, $file, 85);
    imagedestroy($img);
    echo "Created: real_" . ($i+1) . ".jpg\n";
}
echo "\nDone!\n";
