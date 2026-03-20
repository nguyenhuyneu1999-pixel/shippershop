<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Check all conversations between user 3 and user 18
echo "=== Convs between user 3 & 18 ===\n";
$cvs=$d->fetchAll("SELECT * FROM conversations WHERE (user1_id=3 AND user2_id=18) OR (user1_id=18 AND user2_id=3)");
echo json_encode($cvs, JSON_PRETTY_PRINT)."\n";

// Check latest messages
echo "\n=== Latest messages in those convs ===\n";
foreach($cvs as $cv){
  $msgs=$d->fetchAll("SELECT * FROM messages WHERE conversation_id=? ORDER BY id DESC LIMIT 3",[$cv['id']]);
  echo "Conv ".$cv['id'].": ".count($msgs)." msgs\n";
  foreach($msgs as $m) echo "  msg ".$m['id'].": ".$m['content']."\n";
}

// Check for orphaned conversations (no user IDs, created by debug)
echo "\n=== Recent convs (potential orphans) ===\n";
$recent=$d->fetchAll("SELECT id,type,user1_id,user2_id,creator_id,name,`status` FROM conversations WHERE id > 100 ORDER BY id DESC");
echo json_encode($recent, JSON_PRETTY_PRINT)."\n";

// Check the messages table type column - maybe type='text' explicit is needed?
echo "\n=== Test INSERT message with type ===\n";
$testCv=$cvs[0]['id']??null;
if($testCv){
  try{
    $pdo=db()->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt=$pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,created_at) VALUES (?,?,?,NOW())");
    $stmt->execute([$testCv, 3, 'pdo direct test']);
    echo "PDO direct INSERT OK, lastInsertId=".$pdo->lastInsertId()."\n";
    $pdo->exec("DELETE FROM messages WHERE content='pdo direct test'");
    echo "Cleaned\n";
  }catch(PDOException $e){
    echo "PDO ERROR: ".$e->getMessage()."\n";
  }
}
