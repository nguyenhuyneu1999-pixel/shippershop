<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$r['user_badges']=$pdo->query("DESCRIBE user_badges")->fetchAll(PDO::FETCH_COLUMN);}catch(\Throwable $e){$r['user_badges_err']=$e->getMessage();}
echo json_encode($r);
