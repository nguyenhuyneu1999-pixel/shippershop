<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

$cols=$pdo->query("DESCRIBE posts")->fetchAll(PDO::FETCH_COLUMN);
$r['posts_columns']=$cols;

// Check if type column exists
$hasType=in_array('type',$cols);
$r['has_type']=$hasType;

// Check suggest - needs fullText
$r['suggest_test']=db()->fetchAll("SELECT id,fullname FROM users WHERE `status`='active' AND fullname LIKE ? LIMIT 3",['%admin%']);

echo json_encode($r,JSON_PRETTY_PRINT);
