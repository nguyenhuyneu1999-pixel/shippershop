<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS auto_renew TINYINT DEFAULT 1");$r['auto_renew']='OK';}catch(\Throwable $e){$r['auto_renew']=$e->getMessage();}
echo json_encode($r);
