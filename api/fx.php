<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$pdo->exec("CREATE TABLE IF NOT EXISTS post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(20) NOT NULL DEFAULT 'like',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user_post (post_id, user_id),
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
)");$r['post_reactions']='OK';}catch(\Throwable $e){$r['post_reactions']=$e->getMessage();}

// Activity feed table
try{$pdo->exec("CREATE TABLE IF NOT EXISTS activity_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(30) NOT NULL,
    target_type VARCHAR(20),
    target_id INT,
    detail TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, created_at),
    INDEX idx_time (created_at)
)");$r['activity_feed']='OK';}catch(\Throwable $e){$r['activity_feed']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['tables']=intval($tc['c']);
echo json_encode($r);
