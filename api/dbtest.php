<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$admin=$d->fetchOne("SELECT id,username,email,fullname,role FROM users WHERE id=2");
echo "Admin user:\n";
foreach($admin as $k=>$v) echo "  $k: $v\n";

// Test the dashboard_stats directly
echo "\n=== Direct dashboard_stats test ===\n";
$filter='all';
$seedMin=3;$seedMax=102;
$totalUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>1")['c'];
$realUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE (id=2 OR id>$seedMax)")['c'];
echo "Users: total=$totalUsers, real=$realUsers\n";

$totalPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c'];
echo "Posts: $totalPosts\n";

$topPosters=$d->fetchAll("SELECT u.id,u.fullname,COUNT(p.id) as pc FROM users u JOIN posts p ON u.id=p.user_id WHERE p.`status`='active' AND u.id>1 GROUP BY u.id ORDER BY pc DESC LIMIT 5");
foreach($topPosters as $tp) echo "  ".$tp['fullname'].": ".$tp['pc']." posts\n";

$postsByDay=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as cnt FROM posts WHERE `status`='active' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
echo "\nPosts by day:\n";
foreach($postsByDay as $pd) echo "  ".$pd['day'].": ".$pd['cnt']."\n";

echo "DONE\n";
