<?php
require_once __DIR__.'/../includes/db.php';
header("Content-Type: text/plain");
$d=db();
echo "=== group_post_comments columns ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM group_post_comments");
foreach($cols as $c) echo "  {$c['Field']} ({$c['Type']}) {$c['Default']}\n";

echo "\n=== Sample comment ===\n";
$s=$d->fetchOne("SELECT * FROM group_post_comments WHERE status='active' ORDER BY id DESC LIMIT 1");
print_r($s);

echo "\n=== Has group_post_comment_likes table? ===\n";
try{$d->fetchAll("SELECT 1 FROM group_post_comment_likes LIMIT 1");echo "YES\n";}
catch(Exception $e){echo "NO - ".$e->getMessage()."\n";}
