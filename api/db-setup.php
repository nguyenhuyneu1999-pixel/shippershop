<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$dir = '/home/nhshiw2j/public_html/uploads/groups/';
$ttf = '/usr/share/fonts/google-droid/DroidSans-Bold.ttf';

$groups = [
    1  => ['GHTK','#00b14f'],
    2  => ['GRAB','#00b14f'],
    3  => ['SGN', '#EE4D2D'],
    4  => ['HN',  '#d32f2f'],
    5  => ['REV', '#FF9800'],
    6  => ['CF',  '#9C27B0'],
    7  => ['GAS', '#4CAF50'],
    8  => ['J&T', '#d32f2f'],
    9  => ['TIP', '#2196F3'],
    10 => ['Q&A', '#4CAF50'],
    11 => ['ĐN',  '#FF9800'],
    12 => ['GR',  '#9C27B0'],
];

foreach($groups as $id => list($label, $hex)) {
    $r = hexdec(substr($hex,1,2));
    $g = hexdec(substr($hex,3,2));
    $b = hexdec(substr($hex,5,2));
    
    $sz = 400;
    $img = imagecreatetruecolor($sz, $sz);
    imagealphablending($img, true);
    
    // Gradient bg
    for($y=0;$y<$sz;$y++){
        $ratio=$y/$sz;
        $cr=intval($r*(1-$ratio*0.3));
        $cg=intval($g*(1-$ratio*0.3));
        $cb=intval($b*(1-$ratio*0.3));
        $c=imagecolorallocate($img,max(0,$cr),max(0,$cg),max(0,$cb));
        imageline($img,0,$y,$sz-1,$y,$c);
    }
    
    // Subtle circle decoration
    $wa=imagecolorallocatealpha($img,255,255,255,100);
    imagefilledellipse($img,intval($sz*0.75),intval($sz*0.25),intval($sz*0.5),intval($sz*0.5),$wa);
    
    $white=imagecolorallocate($img,255,255,255);
    $shadow=imagecolorallocatealpha($img,0,0,0,80);
    
    // TTF text - auto-size
    $fontSize = mb_strlen($label,'UTF-8') <= 2 ? 140 : (mb_strlen($label,'UTF-8') <= 3 ? 110 : 85);
    $bbox = imagettfbbox($fontSize, 0, $ttf, $label);
    $tw = $bbox[2] - $bbox[0];
    $th = $bbox[1] - $bbox[7];
    $tx = ($sz - $tw) / 2 - $bbox[0];
    $ty = ($sz + $th) / 2 - $bbox[1];
    
    imagettftext($img, $fontSize, 0, intval($tx+3), intval($ty+3), $shadow, $ttf, $label);
    imagettftext($img, $fontSize, 0, intval($tx), intval($ty), $white, $ttf, $label);
    
    // Resize to 200x200
    $final = imagecreatetruecolor(200, 200);
    imagecopyresampled($final, $img, 0, 0, 0, 0, 200, 200, $sz, $sz);
    imagedestroy($img);
    
    imagepng($final, $dir.'group_'.$id.'.png', 9);
    imagedestroy($final);
    echo "Group $id ($label): ".filesize($dir.'group_'.$id.'.png')." bytes\n";
}
echo "\nDONE - HQ avatars with DroidSans-Bold TTF\n";
