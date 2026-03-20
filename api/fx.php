<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Check subscription_plans columns
echo "=== subscription_plans columns ===\n";
$cols = $d->fetchAll("SHOW COLUMNS FROM subscription_plans");
foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";

echo "\n=== subscription_plans data ===\n";
$plans=$d->fetchAll("SELECT * FROM subscription_plans LIMIT 5");
echo json_encode($plans, JSON_PRETTY_PRINT)."\n";

echo "\n=== Test getUserPlan for user 3 ===\n";
require_once __DIR__.'/../includes/functions.php';
try{
  $plan = getUserPlan(3);
  echo json_encode($plan, JSON_PRETTY_PRINT)."\n";
}catch(Throwable $e){
  echo "ERROR: ".$e->getMessage()."\n";
  echo "File: ".$e->getFile()." Line: ".$e->getLine()."\n";
}

echo "\n=== Test checkLimit ===\n";
try{
  $err = checkLimit(3, 'messages_per_month');
  echo "result: ".var_export($err, true)."\n";
}catch(Throwable $e){
  echo "ERROR: ".$e->getMessage()."\n";
  echo "File: ".$e->getFile()." Line: ".$e->getLine()."\n";
}
