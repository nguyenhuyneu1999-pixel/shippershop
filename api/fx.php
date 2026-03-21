<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
try{$pdo->exec("ALTER TABLE bookmark_collections ADD COLUMN IF NOT EXISTS share_key VARCHAR(20) DEFAULT NULL, ADD INDEX idx_share (share_key)");echo json_encode(['ok'=>true]);}catch(\Throwable $e){echo json_encode(['error'=>$e->getMessage()]);}
