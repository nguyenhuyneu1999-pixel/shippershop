<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
echo "=== User roles ===\n";
$users=$d->fetchAll("SELECT id,fullname,email,role FROM users WHERE id IN (2,3,4,724) ORDER BY id");
foreach($users as $u) echo "id={$u['id']} role={$u['role']} name={$u['fullname']} email={$u['email']}\n";
echo "\n=== SHOW COLUMNS role ===\n";
$col=$d->fetchAll("SHOW COLUMNS FROM users LIKE 'role'");
foreach($col as $c) echo "Field={$c['Field']} Type={$c['Type']} Default={$c['Default']}\n";
