<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$pdo->exec("CREATE TABLE IF NOT EXISTS cron_logs (id INT AUTO_INCREMENT PRIMARY KEY, job_name VARCHAR(50), `status` VARCHAR(20), duration_ms INT, message TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_job (job_name), INDEX idx_time (created_at))");$r['cron_logs']='OK';}catch(\Throwable $e){$r['cron_logs']=$e->getMessage();}
try{$pdo->exec("CREATE TABLE IF NOT EXISTS error_logs (id INT AUTO_INCREMENT PRIMARY KEY, level VARCHAR(20), message TEXT, file VARCHAR(255), line INT, trace TEXT, url VARCHAR(500), user_id INT, ip VARCHAR(45), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_level (level), INDEX idx_time (created_at))");$r['error_logs']='OK';}catch(\Throwable $e){$r['error_logs']=$e->getMessage();}
$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['tables']=intval($tc['c']);
echo json_encode($r);
