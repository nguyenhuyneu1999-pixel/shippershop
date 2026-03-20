<?php
session_start();
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/auth-check.php';
header('Content-Type: text/plain');

// Simulate EXACT same flow as send action
echo "=== AUTH CHECK ===\n";
$userId=null;
if(!empty($_SESSION["user_id"])){$userId=intval($_SESSION["user_id"]);echo "Session user: $userId\n";}
if(!$userId){
  try{$userId=getAuthUserId();echo "JWT user: $userId\n";}catch(Throwable $e){echo "JWT fail: ".$e->getMessage()."\n";}
}
if(!$userId){echo "NO AUTH\n";exit;}

echo "\n=== CHECK LIMIT ===\n";
try{
  $limitErr = checkLimit($userId, 'messages_per_month');
  echo "limitErr=".var_export($limitErr,true)."\n";
}catch(Throwable $e){
  echo "checkLimit ERROR: ".$e->getMessage()."\n";
  echo "File: ".$e->getFile()." Line: ".$e->getLine()."\n";
}

echo "\n=== CHECK INPUT ===\n";
$raw=file_get_contents('php://input');
echo "raw input: ".$raw."\n";
$input=json_decode($raw,true);
echo "parsed: ".json_encode($input)."\n";
$oid=intval($input['to_user_id']??0);
$ct=trim($input['content']??'');
echo "oid=$oid ct=$ct\n";
