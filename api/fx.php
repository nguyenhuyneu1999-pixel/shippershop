<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$r=[];

// Check pinned_messages columns
try{
$cols=$pdo->query("DESCRIBE pinned_messages")->fetchAll(PDO::FETCH_ASSOC);
$r['pinned_messages_cols']=array_column($cols,'Field');
}catch(\Throwable $e){$r['pinned_messages']=$e->getMessage();}

// Add user_id column if missing
try{$pdo->exec("ALTER TABLE pinned_messages ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL");$r['add_user_id']='OK';}catch(\Throwable $e){$r['add_user_id']=$e->getMessage();}

echo json_encode($r,JSON_PRETTY_PRINT);
