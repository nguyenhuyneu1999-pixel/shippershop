<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
echo "Total users: ".$d->fetchOne("SELECT COUNT(*) as c FROM users")['c']."\n";
echo "Users with avatar: ".$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE avatar IS NOT NULL AND avatar != ''")['c']."\n";
echo "Total posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM posts")['c']."\n";
echo "Max post id: ".$d->fetchOne("SELECT MAX(id) as m FROM posts")['m']."\n";
echo "Max user id: ".$d->fetchOne("SELECT MAX(id) as m FROM users")['m']."\n";
