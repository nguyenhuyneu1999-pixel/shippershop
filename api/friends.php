<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
$d=db();$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';
try {
if($method==='GET'){
$userId=getAuthUserId();
if($action==='list'){
$friends=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.bio,u.shipping_company,0 as is_online,f.created_at as friend_since FROM friends f JOIN users u ON (CASE WHEN f.user_id=? THEN f.friend_id ELSE f.user_id END)=u.id WHERE (f.user_id=? OR f.friend_id=?) AND f.status='accepted' ORDER BY u.fullname ASC",[$userId,$userId,$userId]);
echo json_encode(['success'=>true,'data'=>$friends]);exit;}
if($action==='requests'){
$reqs=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,f.created_at FROM friends f JOIN users u ON f.user_id=u.id WHERE f.friend_id=? AND f.status='pending' ORDER BY f.created_at DESC",[$userId]);
echo json_encode(['success'=>true,'data'=>$reqs]);exit;}
if($action==='suggestions'){
$sug=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,u.bio FROM users u WHERE u.id!=? AND u.status='active' AND u.id NOT IN (SELECT CASE WHEN user_id=? THEN friend_id ELSE user_id END FROM friends WHERE user_id=? OR friend_id=?) ORDER BY RAND() LIMIT 20",[$userId,$userId,$userId,$userId]);
echo json_encode(['success'=>true,'data'=>$sug]);exit;}
if($action==='online'){
  $limit=intval($_GET['limit']??12);
  $online=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE is_online=1 AND id!=? ORDER BY last_active DESC LIMIT ?",[$userId,$limit]);
  echo json_encode(['success'=>true,'data'=>$online?:[]]);exit;
}}
if($method==='POST'){
$userId=getAuthUserId();$input=json_decode(file_get_contents('php://input'),true);$fid=intval($input['friend_id']??0);
if($action==='add'){
$ex=$d->fetchOne("SELECT id FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)",[$userId,$fid,$fid,$userId]);
if($ex){echo json_encode(['success'=>false,'message'=>'Already sent']);exit;}
$d->query("INSERT INTO friends (user_id,friend_id,status) VALUES (?,?,'pending')",[$userId,$fid]);
echo json_encode(['success'=>true,'message'=>'Sent']);exit;}
if($action==='accept'){$d->query("UPDATE friends SET status='accepted',updated_at=NOW() WHERE user_id=? AND friend_id=? AND status='pending'",[$fid,$userId]);echo json_encode(['success'=>true]);exit;}
if($action==='reject'){$d->query("DELETE FROM friends WHERE user_id=? AND friend_id=? AND status='pending'",[$fid,$userId]);echo json_encode(['success'=>true]);exit;}
if($action==='unfriend'){$d->query("DELETE FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)",[$userId,$fid,$fid,$userId]);echo json_encode(['success'=>true]);exit;}}
} catch(Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}
echo json_encode(['success'=>false,'message'=>'Invalid']);
