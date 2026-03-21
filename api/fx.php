<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

try{$pdo->exec("ALTER TABLE analytics_views ADD COLUMN IF NOT EXISTS ip VARCHAR(45) DEFAULT NULL");$r['av_ip']='OK';}catch(\Throwable $e){$r['av_ip']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE analytics_views ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL");$r['av_uid']='OK';}catch(\Throwable $e){$r['av_uid']=$e->getMessage();}

// Show current columns
$cols=$pdo->query("DESCRIBE analytics_views")->fetchAll(PDO::FETCH_ASSOC);
$r['columns']=array_column($cols,'Field');
$r['total_tables']=intval(db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()")['c']);
echo json_encode($r,JSON_PRETTY_PRINT);
