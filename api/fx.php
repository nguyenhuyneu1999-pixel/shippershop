<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) DEFAULT 'error',
    message VARCHAR(500),
    source VARCHAR(255),
    line_number INT DEFAULT 0,
    stack_trace TEXT,
    page VARCHAR(100),
    user_id INT DEFAULT NULL,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_page (page),
    INDEX idx_created (created_at)
)");
$r['error_logs']='OK';
}catch(\Throwable $e){$r['error_logs']=$e->getMessage();}
$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['tables']=intval($tc['c']);
echo json_encode($r);
