<?php
// ShipperShop API v2 — Conversation Media Gallery
// Browse all shared images/files in a conversation
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function cm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$convId=intval($_GET['conversation_id']??0);
if(!$convId) cm2_fail('Missing conversation_id');

// Verify membership
$member=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$convId,$uid]);
if(!$member) cm2_fail('Khong co quyen',403);

$type=$_GET['type']??'all'; // all, image, file, link
$limit=min(intval($_GET['limit']??30),100);

// Get messages with attachments or links
$where="m.conversation_id=?";$params=[$convId];
if($type==='image') $where.=" AND (m.message_type='image' OR m.content LIKE '%.jpg%' OR m.content LIKE '%.png%' OR m.content LIKE '%.gif%')";
elseif($type==='file') $where.=" AND m.message_type='file'";
elseif($type==='link') $where.=" AND (m.content LIKE '%http://%' OR m.content LIKE '%https://%')";
else $where.=" AND (m.message_type IN ('image','file') OR m.content LIKE '%http%' OR m.content LIKE '%.jpg%' OR m.content LIKE '%.png%')";

$media=$d->fetchAll("SELECT m.id,m.content,m.message_type,m.created_at,u.fullname,u.avatar FROM messages m JOIN users u ON m.sender_id=u.id WHERE $where ORDER BY m.created_at DESC LIMIT $limit",$params);

// Count by type
$counts=['images'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND (message_type='image' OR content LIKE '%.jpg%' OR content LIKE '%.png%')",[$convId])['c']),'files'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND message_type='file'",[$convId])['c']),'links'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND (content LIKE '%http://%' OR content LIKE '%https://%')",[$convId])['c'])];

cm2_ok('OK',['media'=>$media,'counts'=>$counts,'type'=>$type]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
