<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$db=db();
$c=$db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'", []);
echo "Active posts: " . $c['c'] . "\n";
$u=$db->fetchOne("SELECT COUNT(*) as c FROM users WHERE id > 10", []);
echo "Seed users: " . $u['c'] . "\n";
$cm=$db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE `status`='active'", []);
echo "Comments: " . $cm['c'] . "\n";
