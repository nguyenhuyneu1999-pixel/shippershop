<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Simulate conversations API for user 2 (Admin - likely the logged in user)
echo "=== User 2 active conversations (what API returns) ===\n";
$rows=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,last_message_at,`status`,type FROM conversations WHERE (user1_id=2 OR user2_id=2) AND `status`='active' AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC");
foreach($rows as $c){
  $oid=($c['user1_id']==2)?$c['user2_id']:$c['user1_id'];
  $other=$d->fetchOne("SELECT fullname,shipping_company FROM users WHERE id=?",[$oid]);
  echo "conv=".$c['id']." other=$oid (".($other?$other['fullname']:'?').") last=".$c['last_message_at']." type=".$c['type']."\n";
}

echo "\n=== User 2 pending conversations ===\n";
$rows2=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,`status`,type FROM conversations WHERE (user1_id=2 OR user2_id=2) AND `status`='pending' AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC");
foreach($rows2 as $c){
  $oid=($c['user1_id']==2)?$c['user2_id']:$c['user1_id'];
  $other=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$oid]);
  echo "conv=".$c['id']." other=$oid (".($other?$other['fullname']:'?').") status=".$c['status']."\n";
}

echo "\n=== User 3 active conversations ===\n";
$rows3=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,last_message_at,`status`,type FROM conversations WHERE (user1_id=3 OR user2_id=3) AND `status`='active' AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC");
foreach($rows3 as $c){
  $oid=($c['user1_id']==3)?$c['user2_id']:$c['user1_id'];
  $other=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$oid]);
  echo "conv=".$c['id']." other=$oid (".($other?$other['fullname']:'?').") last=".$c['last_message_at']."\n";
}

echo "\n=== User 3 pending conversations ===\n";
$rows4=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,`status`,type FROM conversations WHERE (user1_id=3 OR user2_id=3) AND `status`='pending' AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC");
foreach($rows4 as $c){
  $oid=($c['user1_id']==3)?$c['user2_id']:$c['user1_id'];
  $other=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$oid]);
  echo "conv=".$c['id']." other=$oid (".($other?$other['fullname']:'?').") status=".$c['status']."\n";
}

// Check messages action query
echo "\n=== Messages in conv 7 (user 2 <-> Ngô Thị Thảo) ===\n";
$msgs=$d->fetchAll("SELECT m.*,u.fullname as sender_name FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=7 ORDER BY m.created_at");
foreach($msgs as $m) echo "msg=".$m['id']." from=".$m['sender_name']." content=".$m['content']." at=".$m['created_at']."\n";
if(!$msgs) echo "EMPTY!\n";

echo "\n=== Group conversations ===\n";
$groups=$d->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM conversation_members WHERE conversation_id=c.id) as member_count FROM conversations c WHERE c.type='group'");
foreach($groups as $g) echo "id=".$g['id']." name=".$g['name']." members=".$g['member_count']."\n";

echo "\n=== conversation_members ===\n";
$cm=$d->fetchAll("SELECT * FROM conversation_members ORDER BY conversation_id");
foreach($cm as $m) echo "conv=".$m['conversation_id']." user=".$m['user_id']." role=".$m['role']."\n";
