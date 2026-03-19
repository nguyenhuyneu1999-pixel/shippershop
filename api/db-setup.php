<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== BUG 1 DEEP: Recent comments + timing ===\n";
$recent=$d->fetchAll("SELECT c.id,c.post_id,c.user_id,u.fullname,c.content,c.status,c.created_at 
    FROM group_post_comments c 
    JOIN users u ON c.user_id=u.id 
    ORDER BY c.id DESC LIMIT 10");
foreach($recent as $r) {
    echo "id={$r['id']} post={$r['post_id']} user={$r['fullname']} status={$r['status']} time={$r['created_at']} → {$r['content']}\n";
}

echo "\n=== Are there ANY comments from real users (id 2-8)? ===\n";
$real=$d->fetchAll("SELECT c.id,c.post_id,c.user_id,u.fullname,c.content,c.created_at 
    FROM group_post_comments c 
    JOIN users u ON c.user_id=u.id 
    WHERE c.user_id IN (2,3,4,5,6,7,8)
    ORDER BY c.id DESC LIMIT 5");
if($real) {
    foreach($real as $r) echo "  id={$r['id']} post={$r['post_id']} user={$r['fullname']} → {$r['content']}\n";
} else {
    echo "  NO comments from real users → user may never have successfully commented\n";
}

echo "\n=== BUG 2: Check category action response ===\n";
// Test category action
$catSlug = 'hang-van-chuyen';
$cat = $d->fetchOne("SELECT * FROM group_categories WHERE slug = ? AND parent_id IS NULL", [$catSlug]);
echo "Category: id={$cat['id']} name={$cat['name']}\n";

$subs = $d->fetchAll("SELECT * FROM group_categories WHERE parent_id = ? ORDER BY sort_order", [$cat['id']]);
echo "Subcategories: ".count($subs)."\n";
foreach($subs as $s) echo "  id={$s['id']} name={$s['name']} slug={$s['slug']}\n";

echo "\n=== groups.php category action handler ===\n";
echo "Checking if sub-chip onclick exists in code...\n";

echo "\n=== groups.php all POST actions ===\n";
