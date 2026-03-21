<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
$r['map_pins']=$pdo->query("DESCRIBE map_pins")->fetchAll(PDO::FETCH_COLUMN);
$r['users_has_province']=in_array('province',$pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN));
$r['post5']=db()->fetchOne("SELECT id,`status` FROM posts WHERE id=5");
echo json_encode($r,JSON_PRETTY_PRINT);
