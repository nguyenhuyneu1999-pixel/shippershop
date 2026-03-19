<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$users=$d->fetchAll("SELECT id,email,fullname,username FROM users WHERE id<=10 ORDER BY id");
foreach($users as $u) echo "id={$u['id']} email={$u['email']} user={$u['username']} name={$u['fullname']}\n";

echo "\nReal users (not seed):\n";
$real=$d->fetchAll("SELECT id,email,fullname,username FROM users WHERE email NOT LIKE '%@shippershop.vn' AND email NOT LIKE '%@shippershop.local' ORDER BY id LIMIT 10");
foreach($real as $u) echo "id={$u['id']} email={$u['email']} name={$u['fullname']}\n";

echo "\nLocal users:\n";
$local=$d->fetchAll("SELECT id,email,fullname FROM users WHERE email LIKE '%@shippershop.local' ORDER BY id LIMIT 5");
foreach($local as $u) echo "id={$u['id']} email={$u['email']} name={$u['fullname']}\n";
