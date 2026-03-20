<?php
require_once __DIR__.'/../includes/db.php';
header("Content-Type: text/plain");
$d = db();
$pdo = $d->getConnection();

echo "=== ALL TABLES ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo count($tables)." tables\n";
foreach($tables as $t) echo "  $t\n";

echo "\n=== TABLE ROW COUNTS ===\n";
foreach($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        printf("  %-35s %s rows\n", $t, number_format($c));
    } catch(Exception $e) {
        echo "  $t: ERROR\n";
    }
}

echo "\n=== KEY INDEXES ===\n";
$key_tables = ['posts','comments','users','messages','conversations','likes','groups','group_members','group_posts','notifications','wallet_transactions','marketplace_listings','traffic_alerts'];
foreach($key_tables as $t) {
    try {
        $idx = $pdo->query("SHOW INDEX FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_unique(array_column($idx, 'Key_name'));
        echo "  $t: ".implode(', ', $names)."\n";
    } catch(Exception $e) {}
}

echo "\n=== FOREIGN KEYS CHECK ===\n";
$orphan_checks = [
    ["posts", "user_id", "users", "id"],
    ["comments", "post_id", "posts", "id"],
    ["messages", "conversation_id", "conversations", "id"],
    ["group_posts", "group_id", "groups", "id"],
];
foreach($orphan_checks as list($child, $col, $parent, $pcol)) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM `$child` c LEFT JOIN `$parent` p ON c.`$col`=p.`$pcol` WHERE p.`$pcol` IS NULL AND c.`$col` IS NOT NULL")->fetchColumn();
        if ($c > 0) echo "  ⚠️ $child.$col → $parent.$pcol: $c orphans\n";
    } catch(Exception $e) {}
}
echo "  ✅ Orphan check done\n";
