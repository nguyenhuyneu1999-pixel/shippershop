<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
$d=db();$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';
try {
if($method==='GET'){
if($action==='detail'){
$slug=$_GET['slug']??'';$userId=getOptionalAuthUserId();
$group=$d->fetchOne("SELECT g.*,u.fullname as creator_name FROM `groups` g JOIN users u ON g.creator_id=u.id WHERE g.slug=? AND g.status='active'",[$slug]);
if(!$group){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
$isMember=false;$memberRole=null;
if($userId){$m=$d->fetchOne("SELECT role FROM group_members WHERE group_id=? AND user_id=?",[$group['id'],$userId]);if($m){$isMember=true;$memberRole=$m['role'];}}
$group['is_member']=$isMember;$group['member_role']=$memberRole;
echo json_encode(['success'=>true,'data'=>$group]);exit;}
if($action==='posts'){
$gid=intval($_GET['group_id']??0);$page=max(1,intval($_GET['page']??1));$limit=15;$offset=($page-1)*$limit;
$total=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id=? AND status='active'",[$gid]);
$posts=$d->fetchAll("SELECT gp.*,u.fullname as user_name,u.avatar as user_avatar,u.username as user_username,u.shipping_company FROM group_posts gp JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.status='active' ORDER BY gp.created_at DESC LIMIT $limit OFFSET $offset",[$gid]);
echo json_encode(['success'=>true,'data'=>['posts'=>$posts,'total'=>intval($total['c']??0),'total_pages'=>ceil(intval($total['c']??0)/$limit)]]);exit;}
if($action==='members'){
$gid=intval($_GET['group_id']??0);
$members=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,gm.role,gm.joined_at FROM group_members gm JOIN users u ON gm.user_id=u.id WHERE gm.group_id=? ORDER BY FIELD(gm.role,'admin','moderator','member'),gm.joined_at",[$gid]);
echo json_encode(['success'=>true,'data'=>$members]);exit;}
$search=$_GET['search']??'';$category=$_GET['category']??'';$where="WHERE g.status='active'";$params=[];
if($search){$where.=" AND (g.name LIKE ? OR g.description LIKE ?)";$params[]="%$search%";$params[]="%$search%";}
if($category){$where.=" AND g.category=?";$params[]=$category;}
$userId=getOptionalAuthUserId();
$groups=$d->fetchAll("SELECT g.*,u.fullname as creator_name FROM `groups` g JOIN users u ON g.creator_id=u.id $where ORDER BY g.member_count DESC",$params);
if($userId){$my=$d->fetchAll("SELECT group_id FROM group_members WHERE user_id=?",[$userId]);$myIds=array_column($my,'group_id');foreach($groups as &$g){$g['is_member']=in_array($g['id'],$myIds);}}
echo json_encode(['success'=>true,'data'=>$groups]);exit;}
if($method==='POST'){
$userId=getAuthUserId();$input=json_decode(file_get_contents('php://input'),true);
if($action==='join'){
$gid=intval($input['group_id']??0);$ex=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$gid,$userId]);
if($ex){$d->query("DELETE FROM group_members WHERE group_id=? AND user_id=?",[$gid,$userId]);$d->query("UPDATE `groups` SET member_count=member_count-1 WHERE id=?",[$gid]);echo json_encode(['success'=>true,'joined'=>false]);}
else{$d->query("INSERT INTO group_members (group_id,user_id,role) VALUES (?,?,'member')",[$gid,$userId]);$d->query("UPDATE `groups` SET member_count=member_count+1 WHERE id=?",[$gid]);echo json_encode(['success'=>true,'joined'=>true]);}exit;}
if($action==='post'){
$gid=intval($input['group_id']??0);$content=trim($input['content']??'');
if(!$content){echo json_encode(['success'=>false,'message'=>'Empty']);exit;}
$member=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$gid,$userId]);
if(!$member){echo json_encode(['success'=>false,'message'=>'Not member']);exit;}
$d->query("INSERT INTO group_posts (group_id,user_id,content,type) VALUES (?,?,?,?)",[$gid,$userId,$content,$input['type']??'post']);
$d->query("UPDATE `groups` SET post_count=post_count+1 WHERE id=?",[$gid]);
echo json_encode(['success'=>true,'message'=>'OK']);exit;}
if($action==='create'){
$name=trim($input['name']??'');if(!$name){echo json_encode(['success'=>false,'message'=>'Empty name']);exit;}
$slug=preg_replace('/[^a-z0-9]+/','-',mb_strtolower($name));$slug=trim($slug,'-');
$ex=$d->fetchOne("SELECT id FROM `groups` WHERE slug=?",[$slug]);if($ex)$slug.='-'.time();
$d->query("INSERT INTO `groups` (name,slug,description,rules,creator_id,category) VALUES (?,?,?,?,?,?)",[$name,$slug,$input['description']??'',$input['rules']??'',$userId,$input['category']??'general']);
$gid=$d->getLastInsertId();$d->query("INSERT INTO group_members (group_id,user_id,role) VALUES (?,?,'admin')",[$gid,$userId]);
echo json_encode(['success'=>true,'data'=>['id'=>$gid,'slug'=>$slug]]);exit;}}
} catch(Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}
echo json_encode(['success'=>false,'message'=>'Invalid']);
