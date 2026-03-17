<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors',1);
$d=db();
try{$userId=getAuthUserId();echo "userId: $userId\n";}catch(Throwable $e){echo "Auth: ".$e->getMessage()."\n";exit;}
try{
    $cols=$d->fetchAll("SHOW COLUMNS FROM conversations",[]);
    echo "Columns: ";foreach($cols as $c)echo $c['Field'].", ";echo "\n";
}catch(Throwable $e){echo "Col: ".$e->getMessage()."\n";}
try{
    $convs=$d->fetchAll("SELECT * FROM conversations WHERE user1_id=? OR user2_id=?",[$userId,$userId]);
    echo "Convs: ".count($convs)."\n";
    foreach($convs as $c)echo "  id=".$c['id']." u1=".$c['user1_id']." u2=".$c['user2_id']." status=".($c['status']??'null')."\n";
}catch(Throwable $e){echo "Conv: ".$e->getMessage()."\n";}
try{
    $msgs=$d->fetchAll("SELECT * FROM messages ORDER BY id DESC LIMIT 5",[]);
    echo "Msgs: ".count($msgs)."\n";
    foreach($msgs as $m)echo "  id=".$m['id']." conv=".$m['conversation_id']." from=".$m['sender_id']." text=".$m['content']."\n";
}catch(Throwable $e){echo "Msg: ".$e->getMessage()."\n";}
