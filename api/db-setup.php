<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== FIX: Map groups to correct sub-categories ===\n";

// Group → subcategory mapping
$maps = [
    1 => 10,  // Shipper GHTK → GHTK subcategory
    2 => 16,  // Shipper Grab - Be - Gojek → Grab/Be/Gojek
    8 => 12,  // Shipper J&T SPX Ninja Van → J&T (primary)
    3 => 21,  // Shipper Sài Gòn → HCM
    4 => 20,  // Shipper Hà Nội → Hà Nội
    11 => 22, // Shipper Đà Nẵng → Đà Nẵng
];

foreach($maps as $gid => $catId) {
    $d->query("UPDATE `groups` SET category_id = ? WHERE id = ?", [$catId, $gid]);
    $grp = $d->fetchOne("SELECT name FROM `groups` WHERE id = ?", [$gid]);
    $cat = $d->fetchOne("SELECT name FROM group_categories WHERE id = ?", [$catId]);
    echo "Group {$gid} ({$grp['name']}) → category {$catId} ({$cat['name']})\n";
}

echo "\n=== VERIFY ===\n";
$groups=$d->fetchAll("SELECT g.id,g.name,g.category_id,gc.name as cat_name,gc.parent_id FROM `groups` g LEFT JOIN group_categories gc ON g.category_id=gc.id ORDER BY g.id");
foreach($groups as $g) {
    $parent=$g['parent_id']?'(sub of '.$g['parent_id'].')':'(top-level)';
    echo "id={$g['id']} {$g['name']} → cat={$g['category_id']} ({$g['cat_name']}) {$parent}\n";
}

echo "\n=== TEST: GHTK filter should now return 1 group ===\n";
$cat=$d->fetchOne("SELECT * FROM group_categories WHERE slug='ghtk'");
$catIds=[$cat['id']];
$subs=$d->fetchAll("SELECT id FROM group_categories WHERE parent_id=?",[$cat['id']]);
foreach($subs as $s) $catIds[]=$s['id'];
$ph=implode(',',array_fill(0,count($catIds),'?'));
$groups=$d->fetchAll("SELECT name FROM `groups` WHERE `status`='active' AND category_id IN ($ph)",$catIds);
echo "GHTK groups: ".count($groups)."\n";
foreach($groups as $g) echo "  {$g['name']}\n";

echo "\nDONE\n";
