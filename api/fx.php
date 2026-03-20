<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();
$pdo=$d->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clean orphan convs from debug (user 999/998)
$pdo->exec("DELETE FROM conversations WHERE user1_id=999 AND user2_id=998");

// Clean empty conv between 3 and 18 that was created by debug (no messages)
$empty=$d->fetchAll("SELECT c.id FROM conversations c LEFT JOIN messages m ON m.conversation_id=c.id WHERE c.user1_id=3 AND c.user2_id=18 AND m.id IS NULL");
foreach($empty as $e){
  $pdo->exec("DELETE FROM conversations WHERE id=".$e['id']);
}

// Test: simulate actual send via PDO
$userId=3; $oid=18; $ct="PDO test send";
$cv=$pdo->prepare("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
$cv->execute([$userId,$oid,$oid,$userId]);
$row=$cv->fetch(PDO::FETCH_ASSOC);

if(!$row){
  $ic=$pdo->prepare("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (?,?,?,NOW(),'pending')");
  $ic->execute([$userId,$oid,$ct]);
  $cid=intval($pdo->lastInsertId());
  if(!$cid){$fc=$pdo->query("SELECT MAX(id) as m FROM conversations");$cid=intval($fc->fetch(PDO::FETCH_ASSOC)['m']);}
  echo json_encode(["step"=>"created conv","cid"=>$cid]);
}else{
  $cid=intval($row['id']);
  echo json_encode(["step"=>"found conv","cid"=>$cid]);
}

// Insert msg
$im=$pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,created_at) VALUES (?,?,?,NOW())");
$im->execute([$cid,$userId,$ct]);
$mid=intval($pdo->lastInsertId());

// Verify
$msg=$d->fetchOne("SELECT * FROM messages WHERE id=?",[$mid]);

// Cleanup test msg
$pdo->exec("DELETE FROM messages WHERE id=$mid");

echo json_encode(["mid"=>$mid,"msg_found"=>!!$msg,"content"=>$msg?$msg['content']:null,"SUCCESS"=>true], JSON_PRETTY_PRINT);
