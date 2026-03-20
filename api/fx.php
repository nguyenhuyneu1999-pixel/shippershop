<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();
$cols = $d->fetchAll("SHOW COLUMNS FROM messages");
echo json_encode(['messages_columns'=>array_map(function($c){return ['name'=>$c['Field'],'type'=>$c['Type'],'null'=>$c['Null'],'default'=>$c['Default']];}, $cols)], JSON_PRETTY_PRINT);
