<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS mentions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT DEFAULT NULL,
    comment_id INT DEFAULT NULL,
    mentioned_user_id INT NOT NULL,
    mentioner_user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mentioned (mentioned_user_id),
    INDEX idx_post (post_id)
)");
$r['mentions']='OK';
}catch(\Throwable $e){$r['mentions']=$e->getMessage();}

try{
$pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS read_at DATETIME DEFAULT NULL");
$pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS message_type VARCHAR(20) DEFAULT 'text'");
$r['messages_cols']='OK';
}catch(\Throwable $e){$r['messages_cols']=$e->getMessage();}

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS pinned_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    pinned_by INT NOT NULL,
    pin_type VARCHAR(20) DEFAULT 'profile',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_pin (post_id, pin_type)
)");
$r['pinned_posts']='OK';
}catch(\Throwable $e){$r['pinned_posts']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['total_tables']=intval($tc['c']);
echo json_encode($r);
