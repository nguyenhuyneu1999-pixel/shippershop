<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== notifications table ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM notifications");
foreach($cols as $c) echo $c['Field']." | ".$c['Type']." | ".$c['Default']."\n";

echo "\n=== Sample notifications ===\n";
$notifs=$d->fetchAll("SELECT n.*, u.fullname as actor_name FROM notifications n LEFT JOIN users u ON n.actor_id=u.id ORDER BY n.id DESC LIMIT 10");
foreach($notifs as $n){
    echo "id=".$n['id']." | type=".$n['type']." | user=".$n['user_id']." | actor=".$n['actor_id']."(".$n['actor_name'].") | ref=".$n['reference_id']." | msg=".substr($n['message']??'',0,50)." | read=".($n['is_read']??'?')." | ".$n['created_at']."\n";
}

echo "\n=== Total notifications ===\n";
echo $d->fetchOne("SELECT COUNT(*) as c FROM notifications")['c']."\n";

echo "\n=== Notification types ===\n";
$types=$d->fetchAll("SELECT type, COUNT(*) as c FROM notifications GROUP BY type ORDER BY c DESC");
foreach($types as $t) echo "  ".$t['type'].": ".$t['c']."\n";
