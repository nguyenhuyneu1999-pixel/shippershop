<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d = db();
$tables = $d->fetchAll("SELECT table_name, ROUND(data_length/1024/1024, 2) as data_mb, ROUND(index_length/1024/1024, 2) as index_mb, table_rows FROM information_schema.tables WHERE table_schema = 'nhshiw2j_shippershop' ORDER BY data_length DESC");
$total = 0;
foreach($tables as $t) {
    $sz = $t['data_mb'] + $t['index_mb'];
    $total += $sz;
    if($sz > 0.01) echo $t['table_name'] . ": " . $sz . " MB (" . $t['table_rows'] . " rows)\n";
}
echo "\n=== TOTAL: " . round($total, 2) . " MB ===\n";

// Disk usage
echo "\n=== UPLOADS SIZE ===\n";
$dirs = ['posts', 'videos', 'avatars', 'messages', 'traffic'];
foreach($dirs as $dir) {
    $path = '/home/nhshiw2j/public_html/uploads/' . $dir;
    if(is_dir($path)) {
        $size = 0;
        $count = 0;
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if($file->isFile()) { $size += $file->getSize(); $count++; }
        }
        echo "$dir: " . round($size/1024/1024, 1) . " MB ($count files)\n";
    }
}
