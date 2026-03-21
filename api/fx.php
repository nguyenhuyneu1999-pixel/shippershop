<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$results=[];

// Stories table
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    background VARCHAR(50) DEFAULT '#7C3AED',
    font_size INT DEFAULT 18,
    view_count INT DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
)");
$results['stories']='OK';
}catch(\Throwable $e){$results['stories']=$e->getMessage();}

// Story views
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS story_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_view (story_id, user_id)
)");
$results['story_views']='OK';
}catch(\Throwable $e){$results['story_views']=$e->getMessage();}

// Bookmark collections
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS bookmark_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '📁',
    post_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)");
$results['bookmark_collections']='OK';
}catch(\Throwable $e){$results['bookmark_collections']=$e->getMessage();}

// Bookmark items (link saved_posts to collections)
try{
$pdo->exec("CREATE TABLE IF NOT EXISTS bookmark_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_item (collection_id, post_id),
    INDEX idx_user (user_id)
)");
$results['bookmark_items']='OK';
}catch(\Throwable $e){$results['bookmark_items']=$e->getMessage();}

// User verification
try{
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified TINYINT DEFAULT 0");
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS verified_at DATETIME DEFAULT NULL");
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_note VARCHAR(255) DEFAULT NULL");
$results['user_verified']='OK';
}catch(\Throwable $e){$results['user_verified']=$e->getMessage();}

// Scheduled posts
try{
$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS scheduled_at DATETIME DEFAULT NULL");
$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_draft TINYINT DEFAULT 0");
$results['scheduled_posts']='OK';
}catch(\Throwable $e){$results['scheduled_posts']=$e->getMessage();}

// Table count
$tc=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
$results['total_tables']=intval($tc['c']);

echo json_encode($results, JSON_PRETTY_PRINT);
