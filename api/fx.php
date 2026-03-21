<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
$r['likes']=$pdo->query("DESCRIBE likes")->fetchAll(PDO::FETCH_COLUMN);
$r['comments']=$pdo->query("DESCRIBE comments")->fetchAll(PDO::FETCH_COLUMN);
$r['group_members']=$pdo->query("DESCRIBE group_members")->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($r,JSON_PRETTY_PRINT);
