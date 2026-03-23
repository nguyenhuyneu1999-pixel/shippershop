<?php
/**
 * SHARE CARD GENERATOR
 * Tạo hình ảnh đẹp cho referral sharing
 * GET ?action=referral&code=SHIP-XXX  → PNG image
 * GET ?action=rank&user_id=X          → Rank card PNG
 */
require_once __DIR__ . '/../includes/db.php';
$d = db();
$action = $_GET['action'] ?? 'referral';

if ($action === 'referral') {
    $code = $_GET['code'] ?? '';
    if (!$code) { header('HTTP/1.1 400'); die('Missing code'); }
    
try {

    $ref = $d->fetchOne("SELECT rc.*, u.fullname, u.avatar, u.shipping_company 
        FROM referral_codes rc JOIN users u ON rc.user_id = u.id WHERE rc.code = ?", [$code]);
    if (!$ref) { header('HTTP/1.1 404'); die('Invalid code'); }
    
    $w = 600; $h = 315; // Facebook share size
    $img = imagecreatetruecolor($w, $h);
    
    // Gradient background
    for ($y = 0; $y < $h; $y++) {
        $r = (int)(124 + ($y / $h) * (91 - 124));
        $g = (int)(58 + ($y / $h) * (33 - 58));
        $b = (int)(237 + ($y / $h) * (182 - 237));
        $c = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $c);
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $light = imagecolorallocate($img, 255, 255, 255);
    $semi = imagecolorallocate($img, 200, 200, 255);
    
    // Try to load font
    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    if (!file_exists($font)) $font = '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf';
    $fontR = str_replace('Bold', '', $font);
    if (!file_exists($fontR)) $fontR = $font;
    
    if (file_exists($font)) {
        imagettftext($img, 12, 0, 20, 35, $semi, $fontR, 'SHIPPERSHOP.VN');
        imagettftext($img, 22, 0, 20, 85, $white, $font, 'Tham gia cong dong');
        imagettftext($img, 22, 0, 20, 115, $white, $font, 'Shipper #1 Viet Nam!');
        imagettftext($img, 14, 0, 20, 160, $semi, $fontR, ($ref['fullname'] ?? 'Shipper') . ' moi ban');
        
        // Code box
        imagefilledroundedrect($img, 20, 180, 300, 230, 10, imagecolorallocate($img, 255, 255, 255));
        imagettftext($img, 18, 0, 40, 215, imagecolorallocate($img, 124, 58, 237), $font, $code);
        
        imagettftext($img, 11, 0, 20, 260, $semi, $fontR, 'Dang ky nhan 7 ngay Plus mien phi!');
        imagettftext($img, 10, 0, 20, 285, $semi, $fontR, 'shippershop.vn/r/' . $code);
        
        // QR-like decoration (right side)
        imagefilledroundedrect($img, 400, 50, 560, 265, 12, imagecolorallocate($img, 255, 255, 255));
        imagettftext($img, 40, 0, 440, 140, imagecolorallocate($img, 124, 58, 237), $font, chr(0xF0) . chr(0x9F) . chr(0x9A) . chr(0x9A));
        imagettftext($img, 11, 0, 420, 180, imagecolorallocate($img, 100, 100, 100), $fontR, 'Quet ma hoac');
        imagettftext($img, 11, 0, 425, 200, imagecolorallocate($img, 100, 100, 100), $fontR, 'truy cap link');
        imagettftext($img, 9, 0, 415, 240, imagecolorallocate($img, 124, 58, 237), $font, '/r/' . $code);
    } else {
        // Fallback: simple text
        imagestring($img, 5, 20, 30, 'SHIPPERSHOP.VN', $white);
        imagestring($img, 5, 20, 80, 'Tham gia cong dong Shipper!', $white);
        imagestring($img, 5, 20, 130, 'Ma moi: ' . $code, $white);
        imagestring($img, 4, 20, 180, '7 ngay Plus mien phi!', $semi);
        imagestring($img, 3, 20, 220, 'shippershop.vn/r/' . $code, $semi);
    }
    
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    imagepng($img);
    imagedestroy($img);
    exit;
}

if ($action === 'rank') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) { header('HTTP/1.1 400'); die('Missing user_id'); }
    
    $user = $d->fetchOne("SELECT u.fullname, u.shipping_company, us.total_xp, us.level, us.current_streak 
        FROM users u LEFT JOIN user_streaks us ON u.id = us.user_id WHERE u.id = ?", [$uid]);
    if (!$user) { header('HTTP/1.1 404'); die('User not found'); }
    
    $badges = [1 => 'Tan binh', 2 => 'Shipper', 3 => 'Pro Shipper', 4 => 'Master', 5 => 'Legend'];
    $lv = $user['level'] ?? 1;
    
    $w = 600; $h = 315;
    $img = imagecreatetruecolor($w, $h);
    
    for ($y = 0; $y < $h; $y++) {
        $r = (int)(15 + ($y / $h) * 10);
        $g = (int)(15 + ($y / $h) * 25);
        $b = (int)(35 + ($y / $h) * 20);
        $c = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $c);
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $purple = imagecolorallocate($img, 124, 58, 237);
    $gray = imagecolorallocate($img, 150, 150, 180);
    
    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    if (!file_exists($font)) $font = '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf';
    
    if (file_exists($font)) {
        imagettftext($img, 10, 0, 20, 30, $gray, $font, 'SHIPPERSHOP RANKING');
        imagettftext($img, 24, 0, 20, 80, $white, $font, $user['fullname'] ?? 'Shipper');
        imagettftext($img, 14, 0, 20, 115, $purple, $font, 'Level ' . $lv . ' - ' . ($badges[$lv] ?? 'Shipper'));
        
        imagettftext($img, 36, 0, 20, 200, $purple, $font, number_format($user['total_xp'] ?? 0));
        imagettftext($img, 14, 0, 20, 225, $gray, $font, 'XP');
        
        imagettftext($img, 36, 0, 250, 200, imagecolorallocate($img, 255, 152, 0), $font, (string)($user['current_streak'] ?? 0));
        imagettftext($img, 14, 0, 250, 225, $gray, $font, 'Streak');
        
        imagettftext($img, 11, 0, 20, 280, $gray, $font, 'shippershop.vn/leaderboard.html');
    }
    
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

function imagefilledroundedrect($img, $x1, $y1, $x2, $y2, $r, $color) {
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

} catch (Throwable $e) { echo json_encode(["success"=>false,"message"=>"Server error"]); }
