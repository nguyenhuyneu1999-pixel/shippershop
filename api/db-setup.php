<?php
// Find any TTF font on server
header('Content-Type: text/plain');
$found = [];
$dirs = ['/usr/share/fonts', '/usr/local/share/fonts', '/home/nhshiw2j'];
foreach($dirs as $d) {
    if(!is_dir($d)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach($it as $f) {
        if(strtolower(pathinfo($f,PATHINFO_EXTENSION))==='ttf') {
            $found[] = $f->getPathname();
            if(count($found) >= 10) break 2;
        }
    }
}
echo count($found) . " TTF fonts found:\n";
foreach($found as $f) echo "  $f\n";
if(!$found) echo "No TTF fonts. Need alternative approach.\n";
