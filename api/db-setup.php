<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== traffic_alerts columns ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM traffic_alerts");
foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";

echo "\n=== test traffic query ===\n";
try {
    $alerts=$d->fetchAll("SELECT ta.*, u.fullname as reporter_name,
        (SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=ta.id AND type='confirm') as confirms,
        (SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=ta.id AND type='deny') as denies
        FROM traffic_alerts ta JOIN users u ON ta.user_id=u.id ORDER BY ta.id DESC LIMIT 5");
    echo count($alerts)." alerts OK\n";
} catch(Throwable $e) {
    echo "ERROR: ".$e->getMessage()."\n";
}
