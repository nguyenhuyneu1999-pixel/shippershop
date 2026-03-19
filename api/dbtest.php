<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== FIND TRULY ORGANIC USERS ===\n\n";

// Check registration pattern - shippershop.local = registered via form
echo "--- @shippershop.local users (registered via web form) ---\n";
$local=$d->fetchAll("SELECT id,username,fullname,email,created_at FROM users WHERE email LIKE '%@shippershop.local' ORDER BY created_at");
foreach($local as $u) echo "  id=".$u['id']." | ".$u['fullname']." | ".$u['email']." | ".$u['created_at']."\n";
echo "Count: ".count($local)."\n";

echo "\n--- @gmail.com users ---\n";
$gmail=$d->fetchAll("SELECT id,username,fullname,email,created_at FROM users WHERE email LIKE '%@gmail.com' ORDER BY id LIMIT 10");
foreach($gmail as $u) echo "  id=".$u['id']." | ".$u['fullname']." | ".$u['email']." | ".$u['created_at']."\n";
echo "Total gmail: ".$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE email LIKE '%@gmail.com'")['c']."\n";

echo "\n--- @shippershop.vn users (seeded by script) ---\n";
echo "Total: ".$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE email LIKE '%@shippershop.vn'")['c']."\n";

echo "\n--- All OTHER email domains ---\n";
$other=$d->fetchAll("SELECT id,username,fullname,email,created_at FROM users WHERE email NOT LIKE '%@shippershop.vn' AND email NOT LIKE '%@shippershop.local' AND email NOT LIKE '%@gmail.com' AND id>1 ORDER BY id");
foreach($other as $u) echo "  id=".$u['id']." | ".$u['fullname']." | ".$u['email']." | ".$u['created_at']."\n";

echo "\n--- Summary ---\n";
echo "Admin (id=2): nguyenhuyneu1999@gmail.com\n";
echo "@shippershop.local (registered via form): ".count($local)."\n";
echo "@shippershop.vn (seeded by script): ".$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE email LIKE '%@shippershop.vn'")['c']."\n";
echo "@gmail.com (need to check which are real): ".$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE email LIKE '%@gmail.com'")['c']."\n";

// Check if gmail users were batch-created
echo "\n--- Gmail users that are NOT admin (id=2) ---\n";
$gmailAll=$d->fetchAll("SELECT id,fullname,email,created_at FROM users WHERE email LIKE '%@gmail.com' AND id!=2 ORDER BY id");
foreach($gmailAll as $u) echo "  id=".$u['id']." | ".$u['fullname']." | ".$u['email']." | ".$u['created_at']."\n";

echo "\nDONE\n";
