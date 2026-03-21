<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$results=[];

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS bookmark_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT NULL,
    post_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$results['bookmark_collections']='OK';
}catch(\Throwable $e){$results['bookmark_collections']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$results['total_tables']=intval($tc['c']);
echo json_encode($results);
