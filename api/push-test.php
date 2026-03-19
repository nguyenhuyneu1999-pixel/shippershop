<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== Raw notifications (last 15) ===\n";
$notifs=$d->fetchAll("SELECT * FROM notifications ORDER BY id DESC LIMIT 15");
foreach($notifs as $n){
    echo "---\n";
    foreach($n as $k=>$v) echo "  $k: ".substr($v??'NULL',0,100)."\n";
}

echo "\n=== Total: ".$d->fetchOne("SELECT COUNT(*) as c FROM notifications")['c']."\n";

echo "\n=== Where notifications are CREATED in code ===\n";
