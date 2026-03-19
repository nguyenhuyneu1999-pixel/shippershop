<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
echo "=== CURRENT GROUPS ===\n";
$groups=$d->fetchAll("SELECT id,name,description,icon_image,cover_image,banner_image,category,member_count,post_count,banner_color FROM `groups` ORDER BY id");
foreach($groups as $g){
    echo "id={$g['id']} | {$g['name']} | cat={$g['category']} | members={$g['member_count']} | posts={$g['post_count']}\n";
    echo "  icon={$g['icon_image']} | cover={$g['cover_image']} | banner={$g['banner_image']} | color={$g['banner_color']}\n";
}

echo "\n=== SEED IMAGES AVAILABLE ===\n";
$imgs = glob('/home/nhshiw2j/public_html/uploads/posts/seed_*.jpg');
echo count($imgs) . " seed images\n";
$imgs2 = glob('/home/nhshiw2j/public_html/uploads/posts/seed_v2_*.jpg');
echo count($imgs2) . " seed_v2 images\n";
$avatars = glob('/home/nhshiw2j/public_html/uploads/avatars/*');
echo count($avatars) . " avatars\n";

echo "\n=== GROUPS API RESPONSE FORMAT ===\n";
// Check how groups.php returns data
$popular = $d->fetchAll("SELECT g.*, (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id=g.id) as real_members FROM `groups` g WHERE g.`status`='active' ORDER BY g.member_count DESC LIMIT 3");
foreach($popular as $g) {
    echo json_encode(['id'=>$g['id'],'name'=>$g['name'],'icon'=>$g['icon_image'],'cover'=>$g['cover_image'],'members'=>$g['member_count']],JSON_UNESCAPED_UNICODE)."\n";
}
