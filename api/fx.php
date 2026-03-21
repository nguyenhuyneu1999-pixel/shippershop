<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

try{$pdo->exec("ALTER TABLE payos_payments ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT NULL");$r['payos_desc']='OK';}catch(\Throwable $e){$r['payos_desc']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE payos_payments ADD COLUMN IF NOT EXISTS checkout_url VARCHAR(500) DEFAULT NULL");$r['payos_url']='OK';}catch(\Throwable $e){$r['payos_url']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE payos_payments ADD COLUMN IF NOT EXISTS qr_code VARCHAR(500) DEFAULT NULL");$r['payos_qr']='OK';}catch(\Throwable $e){$r['payos_qr']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE payos_payments ADD COLUMN IF NOT EXISTS paid_at DATETIME DEFAULT NULL");$r['payos_paid']='OK';}catch(\Throwable $e){$r['payos_paid']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE post_reports ADD COLUMN IF NOT EXISTS detail TEXT DEFAULT NULL");$r['reports_detail']='OK';}catch(\Throwable $e){$r['reports_detail']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE post_reports ADD COLUMN IF NOT EXISTS resolved_by INT DEFAULT NULL");$r['reports_resolved']='OK';}catch(\Throwable $e){$r['reports_resolved']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE post_reports ADD COLUMN IF NOT EXISTS resolved_at DATETIME DEFAULT NULL");$r['reports_resolved_at']='OK';}catch(\Throwable $e){$r['reports_resolved_at']=$e->getMessage();}

echo json_encode($r);
