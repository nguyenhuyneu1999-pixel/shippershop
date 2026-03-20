<?php
// Debug: simulate exact send flow for user 3 -> user 18
session_start();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/auth-check.php';
header('Content-Type: text/plain');

define('DEBUG_MODE', true);
$d=db();
$userId=3; $oid=18; $ct="test debug msg";

echo "=== STEP 1: Check follows ===\n";
$f1=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$userId,$oid]);
$f2=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$oid,$userId]);
$mut=($f1&&$f2);
echo "f1=".json_encode($f1)." f2=".json_encode($f2)." mutual=$mut\n";

echo "\n=== STEP 2: Find conversation ===\n";
$cv=$d->fetchOne("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)",[$userId,$oid,$oid,$userId]);
echo "existing conv=".json_encode($cv)."\n";

if(!$cv){
  echo "\n=== STEP 3: CREATE conversation ===\n";
  $st=$mut?'active':'pending';
  echo "status=$st\n";
  try{
    $d->query("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (?,?,?,NOW(),?)",[$userId,$oid,$ct,$st]);
    echo "INSERT OK\n";
    $cid=$d->getLastInsertId();
    echo "getLastInsertId=$cid\n";
    if(!$cid){$r=$d->fetchOne("SELECT id FROM conversations WHERE user1_id=? AND user2_id=? ORDER BY id DESC LIMIT 1",[$userId,$oid]);$cid=$r['id'];}
    echo "final cid=$cid\n";
  }catch(Throwable $e){
    echo "INSERT CONV ERROR: ".$e->getMessage()."\n";
    exit;
  }
}else{
  $cid=$cv['id'];
  echo "Using existing cid=$cid\n";
  try{
    $d->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$ct,$cid]);
    echo "UPDATE OK\n";
  }catch(Throwable $e){
    echo "UPDATE ERROR: ".$e->getMessage()."\n";
  }
}

echo "\n=== STEP 4: INSERT message (cid=$cid) ===\n";
try{
  $d->query("INSERT INTO messages (conversation_id,sender_id,content,created_at) VALUES (?,?,?,NOW())",[$cid,$userId,$ct]);
  echo "INSERT MSG OK\n";
  $mid=$d->getLastInsertId();
  echo "mid=$mid\n";
}catch(Throwable $e){
  echo "INSERT MSG ERROR: ".$e->getMessage()."\n";
  echo "File: ".$e->getFile()." Line: ".$e->getLine()."\n";
}

// Cleanup
echo "\n=== CLEANUP ===\n";
try{
  $d->query("DELETE FROM messages WHERE conversation_id=? AND sender_id=? AND content=?",[$cid,$userId,$ct]);
  echo "Msg deleted\n";
}catch(Throwable $e){echo "cleanup err: ".$e->getMessage()."\n";}
