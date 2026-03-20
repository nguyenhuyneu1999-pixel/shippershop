<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== Conversations count ===\n";
$c=$d->fetchOne("SELECT COUNT(*) as c FROM conversations");
echo "Total: ".$c['c']."\n";

echo "\n=== Conversations for user 3 (Nguyen Van Huy) ===\n";
$cvs=$d->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id) as msg_count FROM conversations c WHERE c.user1_id=3 OR c.user2_id=3 OR c.creator_id=3 ORDER BY c.id DESC");
foreach($cvs as $cv){
  echo "id=".$cv['id']." type=".$cv['type']." u1=".$cv['user1_id']." u2=".$cv['user2_id']." status=".$cv['status']." msgs=".$cv['msg_count']." last=".$cv['last_message_at']."\n";
}

echo "\n=== Messages count ===\n";
$m=$d->fetchOne("SELECT COUNT(*) as c FROM messages");
echo "Total messages: ".$m['c']."\n";

echo "\n=== Recent messages ===\n";
$msgs=$d->fetchAll("SELECT m.id,m.conversation_id,m.sender_id,m.content,m.created_at FROM messages m ORDER BY m.id DESC LIMIT 10");
foreach($msgs as $msg){
  echo "msg=".$msg['id']." conv=".$msg['conversation_id']." from=".$msg['sender_id']." content=".substr($msg['content'],0,50)." at=".$msg['created_at']."\n";
}

echo "\n=== All conversations ===\n";
$all=$d->fetchAll("SELECT c.id,c.type,c.user1_id,c.user2_id,c.creator_id,c.name,c.`status`,(SELECT COUNT(*) FROM messages WHERE conversation_id=c.id) as msg_count FROM conversations c ORDER BY c.id");
foreach($all as $cv){
  echo "id=".$cv['id']." type=".$cv['type']." u1=".$cv['user1_id']." u2=".$cv['user2_id']." creator=".$cv['creator_id']." name=".($cv['name']?:'').'' ." status=".$cv['status']." msgs=".$cv['msg_count']."\n";
}
