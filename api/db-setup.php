<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== BUG 1: group_post_comments table structure ===\n";
try{
    $cols=$d->fetchAll("SHOW COLUMNS FROM group_post_comments");
    foreach($cols as $c) echo $c['Field']." (".$c['Type'].") default=".$c['Default']." null=".$c['Null']."\n";
}catch(Throwable $e){
    echo "TABLE NOT FOUND: ".$e->getMessage()."\n";
}

echo "\n=== Check: do comments exist? ===\n";
try{
    $cnt=$d->fetchOne("SELECT COUNT(*) as c FROM group_post_comments")['c'];
    echo "Total comments: $cnt\n";
    $recent=$d->fetchAll("SELECT c.id,c.post_id,c.user_id,c.content,c.status,c.created_at FROM group_post_comments c ORDER BY c.id DESC LIMIT 5");
    foreach($recent as $r) echo "  id={$r['id']} post={$r['post_id']} user={$r['user_id']} status={$r['status']} content={$r['content']}\n";
}catch(Throwable $e){
    echo "Error: ".$e->getMessage()."\n";
}

echo "\n=== BUG 2: group_categories + sub_categories ===\n";
try{
    $cats=$d->fetchAll("SELECT * FROM group_categories ORDER BY sort_order");
    foreach($cats as $c) echo "id={$c['id']} name={$c['name']} slug={$c['slug']}\n";
}catch(Throwable $e){
    echo "Error: ".$e->getMessage()."\n";
}

echo "\n=== Check: sub_categories / tags exist? ===\n";
try{
    $d->fetchAll("SELECT * FROM group_sub_categories LIMIT 1");
    echo "group_sub_categories: EXISTS\n";
}catch(Throwable $e){
    echo "group_sub_categories: NOT FOUND\n";
}
try{
    $d->fetchAll("SELECT * FROM group_tags LIMIT 1");
    echo "group_tags: EXISTS\n";
}catch(Throwable $e){
    echo "group_tags: NOT FOUND\n";
}

echo "\n=== groups.php category_groups action? ===\n";
echo "Check if action=category_groups exists in code\n";
