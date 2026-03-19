<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Add missing columns
$adds = [
    "ALTER TABLE payos_payments ADD COLUMN qr_code TEXT AFTER checkout_url",
    "ALTER TABLE payos_payments ADD COLUMN payos_data TEXT AFTER raw_webhook",
    "ALTER TABLE payos_payments MODIFY `status` VARCHAR(20) DEFAULT 'pending'",
];
foreach($adds as $sql){
    try{$d->query($sql);echo "OK: $sql\n";}catch(Throwable $e){echo "SKIP: ".substr($e->getMessage(),0,60)."\n";}
}

echo "\nFinal columns:\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM payos_payments");
foreach($cols as $c) echo "  ".$c['Field']." | ".$c['Type']." | ".$c['Default']."\n";
echo "DONE\n";
