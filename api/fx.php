<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$pdo=$d->getConnection();

echo "=== BEFORE CLEANUP ===\n";
echo "Conversations: ".$d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c']."\n";
echo "Messages: ".$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']."\n";

// 1. Delete orphan conv 113 (created by debug, 0 messages)
$empty113=$d->fetchOne("SELECT id FROM conversations WHERE id=113 AND (SELECT COUNT(*) FROM messages WHERE conversation_id=113)=0");
if($empty113){
  $pdo->exec("DELETE FROM conversations WHERE id=113");
  echo "Deleted orphan conv 113\n";
}

// 2. Delete any conv with user 999/998 (test data)
$pdo->exec("DELETE FROM conversations WHERE user1_id IN (999,998) OR user2_id IN (999,998)");
echo "Cleaned test user convs\n";

// 3. Delete empty conversations created after id=102 with 0 messages (all from debug)
$orphans=$d->fetchAll("SELECT c.id FROM conversations c WHERE c.id > 102 AND (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id)=0");
foreach($orphans as $o){
  $pdo->exec("DELETE FROM conversation_members WHERE conversation_id=".$o['id']);
  $pdo->exec("DELETE FROM conversations WHERE id=".$o['id']);
  echo "Deleted orphan conv ".$o['id']."\n";
}

echo "\n=== AFTER CLEANUP ===\n";
echo "Conversations: ".$d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c']."\n";
echo "Messages: ".$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']."\n";

// 4. Verify all conversations have proper data
echo "\n=== All remaining conversations ===\n";
$all=$d->fetchAll("SELECT c.id,c.type,c.user1_id,c.user2_id,c.creator_id,c.name,c.`status`,(SELECT COUNT(*) FROM messages WHERE conversation_id=c.id) as msg_count FROM conversations c ORDER BY c.last_message_at DESC");
foreach($all as $cv){
  $u1name='';$u2name='';
  if($cv['user1_id']){$u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$cv['user1_id']]);$u1name=$u?$u['fullname']:'';}
  if($cv['user2_id']){$u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$cv['user2_id']]);$u2name=$u?$u['fullname']:'';}
  echo "conv=".$cv['id']." ".$cv['type']." [".$u1name." <-> ".$u2name."] status=".$cv['status']." msgs=".$cv['msg_count'].($cv['name']?" name=".$cv['name']:'')."\n";
}

echo "\n=== SUCCESS ===\n";
