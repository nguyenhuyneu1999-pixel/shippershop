<?php
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain');
$db = db();

$all = $db->fetchAll("SELECT * FROM traffic_alerts ORDER BY id DESC LIMIT 10", []);
echo "Total alerts: " . count($all) . "\n";
foreach ($all as $a) {
    echo "ID={$a['id']} status={$a['status']} cat={$a['category']} expires={$a['expires_at']} content=" . substr($a['content']??'',0,50) . "\n";
}
echo "\nNOW: " . date('Y-m-d H:i:s') . "\n";

// Test the exact query from API
$test = $db->fetchAll("SELECT a.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company FROM traffic_alerts a JOIN users u ON a.user_id = u.id WHERE a.`status`='active' AND a.expires_at > NOW() ORDER BY a.created_at DESC LIMIT 20", []);
echo "Query result: " . count($test) . "\n";
