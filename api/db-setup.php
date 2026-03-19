<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== Groups with category_id ===\n";
$groups=$d->fetchAll("SELECT g.id,g.name,g.category_id,gc.name as cat_name,gc.slug as cat_slug FROM `groups` g LEFT JOIN group_categories gc ON g.category_id=gc.id ORDER BY g.id");
foreach($groups as $g) echo "id={$g['id']} {$g['name']} → category_id={$g['category_id']} ({$g['cat_name']} / {$g['cat_slug']})\n";

echo "\n=== Category tree ===\n";
$cats=$d->fetchAll("SELECT * FROM group_categories ORDER BY parent_id,sort_order");
foreach($cats as $c) echo "id={$c['id']} parent={$c['parent_id']} name={$c['name']} slug={$c['slug']}\n";

echo "\n=== FIX: Map groups to correct sub-categories ===\n";
echo "Shipper GHTK (id=1) → should be category_id=10 (GHTK)\n";
echo "Shipper Grab (id=2) → should be category_id=16 (Grab/Be/Gojek)\n";
echo "Shipper J&T (id=8) → should be category_id=12 (J&T) or 13+14\n";
