<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d = db();
$pdo = $d->getConnection();

$tables = ['user_streaks','user_badges','user_xp'];
$result = [];
foreach($tables as $t){
    try{
        $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
        $result[$t] = array_column($cols, 'Field');
    }catch(\Throwable $e){
        $result[$t] = 'ERROR: '.$e->getMessage();
    }
}
echo json_encode($result, JSON_PRETTY_PRINT);
