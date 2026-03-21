<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();
$r=[];
try{$r['users']=$d->fetchAll("SELECT id,fullname FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 3",['%admin%']);}catch(\Throwable $e){$r['users_err']=$e->getMessage();}
try{$r['groups']=$d->fetchAll("SELECT id,name FROM `groups` WHERE name LIKE ? LIMIT 3",['%shipper%']);}catch(\Throwable $e){$r['groups_err']=$e->getMessage();}
echo json_encode($r,JSON_PRETTY_PRINT);
