<?php
// Generate OpenGraph image for social sharing
$w = 1200; $h = 630; // Standard OG size
$img = imagecreatetruecolor($w, $h);

// Background gradient (#EE4D2D to darker)
for ($y = 0; $y < $h; $y++) {
    $factor = $y / $h;
    $r = (int)(238 * (1 - $factor * 0.3));
    $g = (int)(77 * (1 - $factor * 0.3));
    $b = (int)(45 * (1 - $factor * 0.3));
    $c = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $y, $w, $y, $c);
}

$white = imagecolorallocate($img, 255, 255, 255);
$light = imagecolorallocate($img, 255, 220, 210);

$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
$fontR = str_replace('Bold', '', $font);

if (file_exists($font)) {
    imagettftext($img, 48, 0, 60, 200, $white, $font, 'ShipperShop');
    imagettftext($img, 24, 0, 60, 260, $light, $fontR, 'Cong dong Shipper #1 Viet Nam');
    imagettftext($img, 18, 0, 60, 340, $light, $fontR, 'Tips giao hang | Canh bao GT | Cho do nghe');
    imagettftext($img, 16, 0, 60, 400, $light, $fontR, '700+ bai viet | 100+ shipper | 24/7');
    imagettftext($img, 20, 0, 60, 520, $white, $font, 'shippershop.vn');
} else {
    imagestring($img, 5, 60, 200, 'ShipperShop - Cong dong Shipper #1 Viet Nam', $white);
}

// Decorative circles
for ($i = 0; $i < 8; $i++) {
    $cx = $w - 200 + rand(-100, 100);
    $cy = rand(100, $h - 100);
    $semi = imagecolorallocatealpha($img, 255, 255, 255, 110);
    imagefilledellipse($img, $cx, $cy, rand(40, 120), rand(40, 120), $semi);
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
imagepng($img);
imagedestroy($img);
