<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$db=db();
echo "--- wallets ---\n";
try{$cols=$db->fetchAll("SHOW COLUMNS FROM wallets",[]);foreach($cols as $c) echo $c['Field']." (".$c['Type'].") ".$c['Default']."\n";}catch(Throwable $e){echo "NOT EXISTS\n";}
echo "\n--- wallet_transactions ---\n";
try{$cols=$db->fetchAll("SHOW COLUMNS FROM wallet_transactions",[]);foreach($cols as $c) echo $c['Field']." (".$c['Type'].") ".$c['Default']."\n";}catch(Throwable $e){echo "NOT EXISTS\n";}
echo "\n--- settings ---\n";
try{$cols=$db->fetchAll("SHOW COLUMNS FROM settings",[]);foreach($cols as $c) echo $c['Field']." (".$c['Type'].") ".$c['Default']."\n";}catch(Throwable $e){echo "NOT EXISTS\n";}
