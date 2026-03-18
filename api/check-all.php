<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
$db=db();
$tables=$db->fetchAll("SHOW TABLES",[]);
foreach($tables as $t){
    $name=array_values($t)[0];
    echo "$name\n";
}
