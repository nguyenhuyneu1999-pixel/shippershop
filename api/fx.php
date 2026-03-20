<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();

// Simulate private message send: find conversation between user 3 and Ngô Thị Thảo
$thao=$d->fetchOne("SELECT id FROM users WHERE fullname LIKE '%Ng%Th%Thảo%' LIMIT 1");
$uid3=3; $oid=$thao?$thao['id']:0;
echo "User 3 → Thảo (id=$oid)\n";

// Step 1: Find conversation
try{
$cv=$d->fetchOne("SELECT * FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)",[$uid3,$oid,$oid,$uid3]);
echo "Conv: ".json_encode($cv)."\n";
}catch(Throwable $e){echo "STEP1 ERROR: ".$e->getMessage()."\n";}

// Step 2: Try INSERT message
if($cv){
  $cid=$cv['id'];
  try{
    $d->query("INSERT INTO messages (conversation_id,sender_id,content,created_at) VALUES (?,?,?,NOW())",[$cid,$uid3,"test debug"]);
    echo "INSERT OK\n";
    // Cleanup
    $d->query("DELETE FROM messages WHERE conversation_id=? AND sender_id=? AND content='test debug'",[$cid,$uid3]);
    echo "Cleaned up\n";
  }catch(Throwable $e){echo "STEP2 ERROR: ".$e->getMessage()."\n";}
}

// Step 3: Check if any conversations have NULL ids that could cause issues
$broken=$d->fetchAll("SELECT id,type,user1_id,user2_id,creator_id FROM conversations WHERE (type IS NULL OR type='') OR (user1_id IS NULL AND user2_id IS NULL AND creator_id IS NULL) LIMIT 5");
echo "Broken convs: ".json_encode($broken)."\n";

// Step 4: Check if the newly created group broke something
$recent=$d->fetchAll("SELECT id,type,name,user1_id,user2_id,creator_id,`status` FROM conversations ORDER BY id DESC LIMIT 5");
echo "Recent convs: ".json_encode($recent,JSON_PRETTY_PRINT)."\n";
