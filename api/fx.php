<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Check conversations type column default
$cols = $d->fetchAll("SHOW COLUMNS FROM conversations WHERE Field='type'");
echo "type column: ".json_encode($cols[0])."\n\n";

// Try INSERT without type - same as sendMsg does
try{
  $d->query("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (999,998,'test',NOW(),'pending')");
  $cid=$d->getLastInsertId();
  echo "INSERT OK, id=$cid\n";
  // cleanup
  $d->query("DELETE FROM conversations WHERE id=?",[$cid]);
  echo "Cleaned\n";
}catch(Throwable $e){
  echo "INSERT FAILED: ".$e->getMessage()."\n";
}

// Try INSERT with type
try{
  $d->query("INSERT INTO conversations (type,user1_id,user2_id,last_message,last_message_at,`status`) VALUES ('private',999,998,'test',NOW(),'pending')");
  $cid=$d->getLastInsertId();
  echo "INSERT WITH TYPE OK, id=$cid\n";
  $d->query("DELETE FROM conversations WHERE id=?",[$cid]);
  echo "Cleaned\n";
}catch(Throwable $e){
  echo "INSERT WITH TYPE FAILED: ".$e->getMessage()."\n";
}
