<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS user_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_block (user_id, blocked_id),
    INDEX idx_blocked (blocked_id)
)");
$r['user_blocks']='OK';
}catch(\Throwable $e){$r['user_blocks']=$e->getMessage();}

// Also add edit history for posts
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS post_edits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    old_content TEXT,
    new_content TEXT,
    edited_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id)
)");
$r['post_edits']='OK';
}catch(\Throwable $e){$r['post_edits']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['total_tables']=intval($tc['c']);
echo json_encode($r);
