<?php
require_once __DIR__.'/../includes/db.php';
header("Content-Type: text/plain");
$d=db();

// Create group_post_comment_likes table
try{
$d->getConnection()->exec("CREATE TABLE IF NOT EXISTS group_post_comment_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  comment_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gcl (comment_id, user_id),
  KEY idx_comment (comment_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "✅ group_post_comment_likes table created\n";
}catch(Exception $e){echo "Table: ".$e->getMessage()."\n";}
