<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
$cols=$d->fetchAll("SHOW COLUMNS FROM posts");
foreach($cols as $c) echo $c['Field']." | ".$c['Type']."\n";
