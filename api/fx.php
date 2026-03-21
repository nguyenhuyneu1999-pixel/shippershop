<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS post_polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    question VARCHAR(500) NOT NULL,
    expires_at DATETIME DEFAULT NULL,
    total_votes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_post (post_id)
)");
$r['post_polls']='OK';
}catch(\Throwable $e){$r['post_polls']=$e->getMessage();}

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    text VARCHAR(200) NOT NULL,
    vote_count INT DEFAULT 0,
    INDEX idx_poll (poll_id)
)");
$r['poll_options']='OK';
}catch(\Throwable $e){$r['poll_options']=$e->getMessage();}

try{
$pdo->exec("CREATE TABLE IF NOT EXISTS poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_vote (poll_id, user_id)
)");
$r['poll_votes']='OK';
}catch(\Throwable $e){$r['poll_votes']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['total_tables']=intval($tc['c']);
echo json_encode($r);
