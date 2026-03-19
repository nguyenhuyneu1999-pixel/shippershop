<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');

$dir = '/home/nhshiw2j/public_html/uploads/groups/';
if(!is_dir($dir)) mkdir($dir, 0755, true);

// Better avatars with large centered text using imagestring at scale
$groups = [
    1  => ['GHTK','#00b14f'],
    2  => ['GRAB','#00b14f'],
    3  => ['SGN', '#EE4D2D'],
    4  => ['HNI', '#d32f2f'],
    5  => ['REV', '#FF9800'],
    6  => ['CF',  '#9C27B0'],
    7  => ['GAS', '#4CAF50'],
    8  => ['J&T', '#d32f2f'],
    9  => ['TIP', '#2196F3'],
    10 => ['Q&A', '#4CAF50'],
    11 => ['DNA', '#FF9800'],
    12 => ['GR',  '#9C27B0'],
];

// Check for TTF fonts
$ttf = null;
$fontPaths = ['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'];
foreach($fontPaths as $fp) { if(file_exists($fp)) { $ttf = $fp; break; } }
echo "TTF font: " . ($ttf ?: "NOT FOUND") . "\n\n";

foreach($groups as $id => list($label, $hex)) {
    $r = hexdec(substr($hex,1,2));
    $g = hexdec(substr($hex,3,2));
    $b = hexdec(substr($hex,5,2));
    
    // Darker shade for gradient effect
    $r2 = max(0, $r - 30);
    $g2 = max(0, $g - 30);
    $b2 = max(0, $b - 30);
    
    $size = 400; // High res
    $img = imagecreatetruecolor($size, $size);
    
    // Enable alpha
    imagesavealpha($img, true);
    imagealphablending($img, true);
    
    // Fill gradient-like bg (top lighter, bottom darker)
    for($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $cr = intval($r + ($r2-$r)*$ratio);
        $cg = intval($g + ($g2-$g)*$ratio);
        $cb = intval($b + ($b2-$b)*$ratio);
        $color = imagecolorallocate($img, $cr, $cg, $cb);
        imageline($img, 0, $y, $size-1, $y, $color);
    }
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $whiteA = imagecolorallocatealpha($img, 255, 255, 255, 80);
    
    // Draw subtle circle pattern for texture
    imageellipse($img, $size*0.7, $size*0.3, $size*0.6, $size*0.6, $whiteA);
    
    if($ttf) {
        // TTF rendering - much nicer
        $fontSize = strlen($label) <= 2 ? 120 : (strlen($label) <= 3 ? 90 : 70);
        $bbox = imagettfbbox($fontSize, 0, $ttf, $label);
        $tw = $bbox[2] - $bbox[0];
        $th = $bbox[1] - $bbox[7];
        $tx = ($size - $tw) / 2 - $bbox[0];
        $ty = ($size + $th) / 2 - $bbox[1];
        
        // Shadow
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 90);
        imagettftext($img, $fontSize, 0, $tx+2, $ty+2, $shadow, $ttf, $label);
        // Text
        imagettftext($img, $fontSize, 0, $tx, $ty, $white, $ttf, $label);
    } else {
        // Fallback: use built-in font scaled up
        $font = 5;
        $tw = imagefontwidth($font) * strlen($label);
        $th = imagefontheight($font);
        imagestring($img, $font, ($size-$tw)/2, ($size-$th)/2, $label, $white);
    }
    
    // Resize to 200x200
    $final = imagecreatetruecolor(200, 200);
    imagecopyresampled($final, $img, 0, 0, 0, 0, 200, 200, $size, $size);
    imagedestroy($img);
    
    $path = $dir . 'group_' . $id . '.png';
    imagepng($final, $path, 9);
    imagedestroy($final);
    
    echo "Group $id ($label): OK (" . filesize($path) . " bytes)\n";
}

echo "\nDONE - all group avatars regenerated\n";
