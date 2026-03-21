<?php
// ShipperShop API v2 — Post Drafts Manager
// CRUD for post drafts stored in content_queue
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function dm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function dm_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// List drafts
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='list')){
    $drafts=$d->fetchAll("SELECT id,content,`status`,scheduled_at,created_at FROM content_queue WHERE user_id=? AND `status`='draft' ORDER BY created_at DESC LIMIT 50",[$uid]);
    dm_ok('OK',['drafts'=>$drafts,'count'=>count($drafts)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Save draft
    if(!$action||$action==='save'){
        $content=trim($input['content']??'');
        $draftId=intval($input['draft_id']??0);
        if(!$content) dm_fail('Noi dung trong');

        if($draftId){
            // Update existing
            $exists=$d->fetchOne("SELECT id FROM content_queue WHERE id=? AND user_id=? AND `status`='draft'",[$draftId,$uid]);
            if(!$exists) dm_fail('Draft not found',404);
            $d->query("UPDATE content_queue SET content=?,created_at=NOW() WHERE id=?",[$content,$draftId]);
            dm_ok('Da cap nhat!',['id'=>$draftId]);
        }else{
            // New draft
            $pdo->prepare("INSERT INTO content_queue (user_id,content,`status`,created_at) VALUES (?,?,'draft',NOW())")->execute([$uid,$content]);
            $id=intval($pdo->lastInsertId());
            if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM content_queue");$id=intval($r->fetch(\PDO::FETCH_ASSOC)['m']);}
            dm_ok('Da luu nhap!',['id'=>$id]);
        }
    }

    // Publish draft
    if($action==='publish'){
        $draftId=intval($input['draft_id']??0);
        if(!$draftId) dm_fail('Missing draft_id');
        $draft=$d->fetchOne("SELECT * FROM content_queue WHERE id=? AND user_id=? AND `status`='draft'",[$draftId,$uid]);
        if(!$draft) dm_fail('Draft not found',404);
        // Create post from draft
        $pdo->prepare("INSERT INTO posts (user_id,content,`status`,created_at) VALUES (?,?,'active',NOW())")->execute([$uid,$draft['content']]);
        $postId=intval($pdo->lastInsertId());
        if(!$postId){$r=$pdo->query("SELECT MAX(id) as m FROM posts");$postId=intval($r->fetch(\PDO::FETCH_ASSOC)['m']);}
        // Mark draft as published
        $d->query("UPDATE content_queue SET `status`='published' WHERE id=?",[$draftId]);
        $d->query("UPDATE users SET total_posts=total_posts+1 WHERE id=?",[$uid]);
        dm_ok('Da dang bai!',['post_id'=>$postId]);
    }

    // Delete draft
    if($action==='delete'){
        $draftId=intval($input['draft_id']??0);
        $exists=$d->fetchOne("SELECT id FROM content_queue WHERE id=? AND user_id=? AND `status`='draft'",[$draftId,$uid]);
        if(!$exists) dm_fail('Draft not found',404);
        $d->query("DELETE FROM content_queue WHERE id=? AND user_id=?",[$draftId,$uid]);
        dm_ok('Da xoa nhap');
    }

    dm_fail('Action khong hop le');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
