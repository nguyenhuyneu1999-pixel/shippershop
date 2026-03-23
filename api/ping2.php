<?php
// PHP + file cache read only
$f = '/tmp/ss_mc/' . md5('test') . '.cache';
if (file_exists($f)) {
    $d = file_get_contents($f);
    header('Content-Type: application/json');
    echo '{"ok":true,"cached":true}';
    exit;
}
header('Content-Type: application/json');
@mkdir('/tmp/ss_mc', 0777, true);
file_put_contents($f, str_pad(time()+60, 10, '0', STR_PAD_LEFT) . '{"test":1}');
echo '{"ok":true,"cached":false}';
