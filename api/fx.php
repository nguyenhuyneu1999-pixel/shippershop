<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();$pdo=$d->getConnection();

// Clean test groups (created during testing, id > 113)
$testGroups=$d->fetchAll("SELECT id,name FROM conversations WHERE type='group' AND id>100 AND name LIKE '%Test%' OR name LIKE '%Rename%'");
foreach($testGroups as $g){
    $pdo->exec("DELETE FROM conversation_members WHERE conversation_id=".$g['id']);
    $pdo->exec("DELETE FROM messages WHERE conversation_id=".$g['id']);
    $pdo->exec("DELETE FROM conversations WHERE id=".$g['id']);
}

// Clean orphan test conversations (user 3 → 18 with 0 msgs, from debug)
$orphans=$d->fetchAll("SELECT c.id FROM conversations c WHERE c.id>102 AND c.type='private' AND (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id)=0");
foreach($orphans as $o){$pdo->exec("DELETE FROM conversations WHERE id=".$o['id']);}

// Stats
$convs=$d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c'];
$msgs=$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c'];
echo json_encode(['cleaned'=>count($testGroups)+count($orphans),'conversations'=>$convs,'messages'=>$msgs]);
