<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors',1);
$d=db();
$userId=getAuthUserId();

// Test simple query first
try{
$r=$d->fetchAll("SELECT c.id, c.user1_id, c.user2_id, c.last_message, c.status FROM conversations c WHERE c.user1_id=? OR c.user2_id=?",[$userId,$userId]);
echo "Simple: OK ".count($r)."\n";
}catch(Throwable $e){echo "Simple: ".$e->getMessage()."\n";}

// Test with JOIN but no status filter
try{
$r=$d->fetchAll("SELECT c.id, c.status, u2.fullname as other_name
    FROM conversations c 
    JOIN users u1 ON c.user1_id=u1.id 
    JOIN users u2 ON c.user2_id=u2.id
    WHERE c.user1_id=? OR c.user2_id=?",[$userId,$userId]);
echo "Join: OK ".count($r)."\n";
}catch(Throwable $e){echo "Join: ".$e->getMessage()."\n";}

// Test with status filter
try{
$r=$d->fetchAll("SELECT c.id FROM conversations c WHERE (c.user1_id=? OR c.user2_id=?) AND c.status='active'",[$userId,$userId]);
echo "Status filter: OK ".count($r)."\n";
}catch(Throwable $e){echo "Status filter: ".$e->getMessage()."\n";}

// Test full query with backticks on status
try{
$r=$d->fetchAll("SELECT c.id, c.`status` as conv_status,
    CASE WHEN c.user1_id=? THEN u2.id ELSE u1.id END as other_id,
    CASE WHEN c.user1_id=? THEN u2.fullname ELSE u1.fullname END as other_name
    FROM conversations c JOIN users u1 ON c.user1_id=u1.id JOIN users u2 ON c.user2_id=u2.id
    WHERE (c.user1_id=? OR c.user2_id=?) AND c.`status`=?",
    [$userId,$userId,$userId,$userId,'active']);
echo "Full: OK ".count($r)."\n";
foreach($r as $row) echo "  ".$row['other_id']." ".$row['other_name']."\n";
}catch(Throwable $e){echo "Full: ".$e->getMessage()."\n";}
