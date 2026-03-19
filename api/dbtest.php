<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$rw="(email LIKE '%@shippershop.local' OR email='nguyenhuyneu1999@gmail.com' OR email='nguyenvanhuy12123@gmail.com')";
$real=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>1 AND $rw")['c'];
$seed=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>1 AND NOT $rw")['c'];
echo "Real users: $real\n";
echo "Seed users: $seed\n";
echo "\nReal users list:\n";
$list=$d->fetchAll("SELECT id,fullname,email,created_at FROM users WHERE id>1 AND $rw ORDER BY id");
foreach($list as $u) echo "  id=".$u['id']." | ".$u['fullname']." | ".$u['email']." | ".$u['created_at']."\n";
echo "\nReal posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id IN (SELECT id FROM users WHERE $rw)")['c']."\n";
echo "Seed posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id NOT IN (SELECT id FROM users WHERE $rw)")['c']."\n";
