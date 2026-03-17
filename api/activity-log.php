<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
$d=db();
$userId=getAuthUserId();
$filter=$_GET['filter']??'all';
$dateFrom=$_GET['from']??'';
$dateTo=$_GET['to']??'';
$page=max(1,intval($_GET['page']??1));
$limit=30;$offset=($page-1)*$limit;

try{
$activities=[];

// Posts by user
if($filter==='all'||$filter==='posts'){
  $where="WHERE p.user_id=? AND p.status='active'";$params=[$userId];
  if($dateFrom){$where.=" AND p.created_at>=?";$params[]=$dateFrom.' 00:00:00';}
  if($dateTo){$where.=" AND p.created_at<=?";$params[]=$dateTo.' 23:59:59';}
  $posts=$d->fetchAll("SELECT p.id,p.content,p.images,p.created_at,p.likes_count,p.comments_count,'post' as activity_type FROM posts p $where ORDER BY p.created_at DESC",$params);
  foreach($posts as $p){$activities[]=['type'=>'post','action'=>'Đã đăng bài viết','content'=>$p['content'],'images'=>$p['images'],'post_id'=>$p['id'],'likes'=>$p['likes_count'],'comments'=>$p['comments_count'],'created_at'=>$p['created_at']];}
}

// Comments by user
if($filter==='all'||$filter==='comments'){
  $where="WHERE c.user_id=? AND c.status='active'";$params=[$userId];
  if($dateFrom){$where.=" AND c.created_at>=?";$params[]=$dateFrom.' 00:00:00';}
  if($dateTo){$where.=" AND c.created_at<=?";$params[]=$dateTo.' 23:59:59';}
  $cmts=$d->fetchAll("SELECT c.id,c.content,c.created_at,c.post_id,p.content as post_content,u.fullname as post_author,u.avatar as post_author_avatar FROM comments c JOIN posts p ON c.post_id=p.id JOIN users u ON p.user_id=u.id $where ORDER BY c.created_at DESC",$params);
  foreach($cmts as $c){$activities[]=['type'=>'comment','action'=>'Đã bình luận về bài viết của '.$c['post_author'],'content'=>$c['content'],'post_content'=>$c['post_content'],'post_id'=>$c['post_id'],'post_author'=>$c['post_author'],'post_author_avatar'=>$c['post_author_avatar'],'created_at'=>$c['created_at']];}
}

// Likes by user
if($filter==='all'||$filter==='likes'){
  $where="WHERE l.user_id=?";$params=[$userId];
  if($dateFrom){$where.=" AND l.created_at>=?";$params[]=$dateFrom.' 00:00:00';}
  if($dateTo){$where.=" AND l.created_at<=?";$params[]=$dateTo.' 23:59:59';}
  $likes=$d->fetchAll("SELECT l.created_at,p.id as post_id,p.content as post_content,p.images,u.fullname as post_author,u.avatar as post_author_avatar FROM likes l JOIN posts p ON l.post_id=p.id JOIN users u ON p.user_id=u.id $where ORDER BY l.created_at DESC",$params);
  foreach($likes as $l){$activities[]=['type'=>'like','action'=>'Đã thích bài viết của '.$l['post_author'],'post_content'=>$l['post_content'],'images'=>$l['images']??null,'post_id'=>$l['post_id'],'post_author'=>$l['post_author'],'post_author_avatar'=>$l['post_author_avatar'],'created_at'=>$l['created_at']];}
}

// Saved posts
if($filter==='all'||$filter==='saved'){
  $where="WHERE s.user_id=?";$params=[$userId];
  $saved=$d->fetchAll("SELECT s.created_at,p.id as post_id,p.content,p.images,u.fullname as post_author FROM saved_posts s JOIN posts p ON s.post_id=p.id JOIN users u ON p.user_id=u.id $where ORDER BY s.created_at DESC",$params);
  foreach($saved as $s){$activities[]=['type'=>'save','action'=>'Đã lưu bài viết của '.$s['post_author'],'post_content'=>$s['content'],'images'=>$s['images']??null,'post_id'=>$s['post_id'],'created_at'=>$s['created_at']];}
}

// Sort by date desc
usort($activities,function($a,$b){return strtotime($b['created_at'])-strtotime($a['created_at']);});

// Paginate
$total=count($activities);
$activities=array_slice($activities,$offset,$limit);

// Group by date
$grouped=[];
foreach($activities as $a){
  $date=date('Y-m-d',strtotime($a['created_at']));
  if(!isset($grouped[$date]))$grouped[$date]=[];
  $grouped[$date][]=$a;
}

echo json_encode(['success'=>true,'data'=>['activities'=>$grouped,'total'=>$total,'page'=>$page,'has_more'=>$total>$offset+$limit]]);
}catch(Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}