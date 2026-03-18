<?php
define('APP_ACCESS',true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo=db()->getConnection();
// Update expired alerts + add coordinates
$pdo->exec("UPDATE traffic_alerts SET expires_at=DATE_ADD(NOW(),INTERVAL 4 HOUR), `status`='active' WHERE `status`!='removed'");
// Ensure all have coordinates
$rows=$pdo->query("SELECT id,latitude,longitude FROM traffic_alerts WHERE (latitude IS NULL OR latitude=0)")->fetchAll(PDO::FETCH_ASSOC);
$coords=[[21.03,105.85],[21.01,105.84],[10.78,106.70],[10.80,106.67],[16.05,108.20]];
foreach($rows as $i=>$r){
    $c=$coords[$i%count($coords)];
    $pdo->exec("UPDATE traffic_alerts SET latitude={$c[0]}, longitude={$c[1]} WHERE id={$r['id']}");
}
echo json_encode(["fixed"=>count($rows),"total"=>$pdo->query("SELECT COUNT(*) as c FROM traffic_alerts WHERE `status`='active'")->fetch()['c']]);
