<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d = db();$pdo = $d->getConnection();

$results = [];

// Check indexes on key tables
$tables = ['posts','comments','post_likes','follows','messages','conversations','groups','group_posts','traffic_alerts','marketplace_listings','users','wallets','notifications'];
foreach($tables as $t) {
    $indexes = $pdo->query("SHOW INDEX FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    $results[$t] = array_map(function($i) { return $i['Key_name'].'('.$i['Column_name'].')'; }, $indexes);
}

// Check slow queries
$results['_table_sizes'] = $d->fetchAll("SELECT table_name, table_rows, ROUND(data_length/1024) as data_kb FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_rows DESC LIMIT 15");

echo json_encode($results, JSON_PRETTY_PRINT);
