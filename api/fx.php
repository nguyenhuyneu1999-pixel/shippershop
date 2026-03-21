<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$pdo->exec("CREATE TABLE IF NOT EXISTS polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    question VARCHAR(500),
    allow_multiple TINYINT DEFAULT 0,
    ends_at DATETIME,
    total_votes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_post (post_id)
)");$r['polls']='OK';}catch(\Throwable $e){$r['polls']=$e->getMessage();}

try{$pdo->exec("CREATE TABLE IF NOT EXISTS poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    text VARCHAR(200) NOT NULL,
    vote_count INT DEFAULT 0,
    INDEX idx_poll (poll_id)
)");$r['poll_options']='OK';}catch(\Throwable $e){$r['poll_options']=$e->getMessage();}

try{$pdo->exec("CREATE TABLE IF NOT EXISTS poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user_poll_option (poll_id, option_id, user_id),
    INDEX idx_user (user_id)
)");$r['poll_votes']='OK';}catch(\Throwable $e){$r['poll_votes']=$e->getMessage();}

try{$pdo->exec("CREATE TABLE IF NOT EXISTS admin_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_user_id INT NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target_user_id),
    INDEX idx_admin (admin_id)
)");$r['admin_notes']='OK';}catch(\Throwable $e){$r['admin_notes']=$e->getMessage();}

// Add is_pinned to conversations
try{$pdo->exec("ALTER TABLE conversations ADD COLUMN IF NOT EXISTS is_pinned TINYINT DEFAULT 0");$r['conv_pinned']='OK';}catch(\Throwable $e){$r['conv_pinned']=$e->getMessage();}

$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['tables']=intval($tc['c']);
echo json_encode($r);
