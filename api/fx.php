<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{
$cols=$pdo->query("DESCRIBE user_subscriptions")->fetchAll(PDO::FETCH_ASSOC);
$r['columns']=array_column($cols,'Field');
$r['count']=intval(db()->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions")['c']);
}catch(\Throwable $e){$r['error']=$e->getMessage();}
echo json_encode($r,JSON_PRETTY_PRINT);
