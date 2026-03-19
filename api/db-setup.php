<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== traffic_confirms columns ===\n";
try {
    $cols=$d->fetchAll("SHOW COLUMNS FROM traffic_confirms");
    foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";
} catch(Throwable $e) {
    echo "ERROR: ".$e->getMessage()."\n";
}

echo "\n=== test simpler query ===\n";
try {
    $alerts=$d->fetchAll("SELECT ta.id,ta.content,ta.user_id,u.fullname FROM traffic_alerts ta JOIN users u ON ta.user_id=u.id LIMIT 3");
    echo count($alerts)." OK\n";
    foreach($alerts as $a) echo "  id={$a['id']} user={$a['user_id']} name={$a['fullname']}\n";
} catch(Throwable $e) {
    echo "JOIN ERROR: ".$e->getMessage()."\n";
}

echo "\n=== test confirms subquery ===\n";
try {
    $c=$d->fetchAll("SELECT * FROM traffic_confirms LIMIT 3");
    echo count($c)." confirms\n";
    if($c) foreach($c[0] as $k=>$v) echo "  $k=$v\n";
} catch(Throwable $e) {
    echo "confirms ERROR: ".$e->getMessage()."\n";
}
