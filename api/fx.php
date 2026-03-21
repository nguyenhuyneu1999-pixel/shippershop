<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];
try{$pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS reactions JSON DEFAULT NULL");$r['reactions']='OK';}catch(\Throwable $e){$r['reactions']=$e->getMessage();}
try{$pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS read_at DATETIME DEFAULT NULL");$r['read_at']='OK';}catch(\Throwable $e){$r['read_at']=$e->getMessage();}
// Post mentions
try{$pdo->exec("CREATE TABLE IF NOT EXISTS mentions (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT, comment_id INT, user_id INT NOT NULL, mentioned_user_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_mentioned (mentioned_user_id), INDEX idx_post (post_id))");$r['mentions']='OK';}catch(\Throwable $e){$r['mentions']=$e->getMessage();}
// Pinned posts (for groups/profile)
try{$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_pinned TINYINT DEFAULT 0");$r['pin']='OK';}catch(\Throwable $e){$r['pin']=$e->getMessage();}
$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$r['tables']=intval($tc['c']);
echo json_encode($r);
