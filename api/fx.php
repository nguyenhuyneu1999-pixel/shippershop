<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(20) NOT NULL DEFAULT 'like',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_reaction (post_id, user_id),
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
)");
$r['post_reactions']='OK';
}catch(\Throwable $e){$r['post_reactions']=$e->getMessage();}

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS typing_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_typing (conversation_id, user_id)
)");
$r['typing_indicators']='OK';
}catch(\Throwable $e){$r['typing_indicators']=$e->getMessage();}

try{
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_completion TINYINT DEFAULT 0");
$r['profile_completion']='OK';
}catch(\Throwable $e){$r['profile_completion']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['total_tables']=intval($tc['c']);
echo json_encode($r);
