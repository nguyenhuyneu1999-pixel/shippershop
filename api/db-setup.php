<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Clean up test comments from test user 724
$deleted = $d->query("DELETE FROM comments WHERE user_id = 724");
echo "Deleted test comments from user 724\n";

// Also clean test post
$d->query("DELETE FROM posts WHERE user_id = 724 AND content LIKE 'Test%'");
echo "Deleted test posts from user 724\n";

// Clean deep nested test comments (content = "1") from admin
$deep = $d->fetchAll("SELECT id,parent_id,content,user_id FROM comments WHERE content='1' AND user_id=2 ORDER BY id DESC LIMIT 10");
echo "\nDeep test comments (content='1', user=admin):\n";
foreach($deep as $c) echo "  id={$c['id']} parent={$c['parent_id']}\n";
$d->query("DELETE FROM comments WHERE content='1' AND user_id=2 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
echo "Cleaned recent '1' comments from admin\n";

// Verify
$remaining = $d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE content='1' AND user_id=2")['c'];
echo "Remaining '1' admin comments: $remaining\n";

echo "\nDONE\n";
