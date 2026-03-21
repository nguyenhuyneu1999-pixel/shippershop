<?php
// ShipperShop API v2 — Content Queue Management
// View, prioritize, approve, reject queued auto-content
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

function cq_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cq_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') cq_fail('Admin only',403);

$page=max(1,intval($_GET['page']??1));
$limit=min(intval($_GET['limit']??20),50);
$offset=($page-1)*$limit;

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List queue items
    if(!$action||$action==='list'){
        $status=$_GET['status']??'pending';
        $w="`status`=?";$p=[$status];
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE $w",$p)['c']);
        $items=$d->fetchAll("SELECT * FROM content_queue WHERE $w ORDER BY created_at DESC LIMIT $limit OFFSET $offset",$p);
        cq_ok('OK',['items'=>$items,'total'=>$total,'page'=>$page]);
    }

    // Queue stats
    if($action==='stats'){
        $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='pending'")['c']);
        $published=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published'")['c']);
        $rejected=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='rejected'")['c']);
        $todayPublished=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published' AND published_at>=CURDATE()")['c']);
        cq_ok('OK',['pending'=>$pending,'published'=>$published,'rejected'=>$rejected,'today_published'=>$todayPublished]);
    }

    cq_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Approve + publish now
    if($action==='approve'){
        $qid=intval($input['queue_id']??0);
        if(!$qid) cq_fail('Missing queue_id');
        $item=$d->fetchOne("SELECT * FROM content_queue WHERE id=?",[$qid]);
        if(!$item) cq_fail('Not found',404);

        // Create post from queue item
        $pdo->prepare("INSERT INTO posts (user_id,content,type,`status`,created_at) VALUES (?,?,?,'active',NOW())")->execute([intval($item['user_id']??2),$item['content'],$item['type']??'tip']);
        $d->query("UPDATE content_queue SET `status`='published',published_at=NOW() WHERE id=?",[$qid]);
        cq_ok('Đã duyệt và đăng');
    }

    // Reject
    if($action==='reject'){
        $qid=intval($input['queue_id']??0);
        if(!$qid) cq_fail('Missing queue_id');
        $d->query("UPDATE content_queue SET `status`='rejected' WHERE id=?",[$qid]);
        cq_ok('Đã từ chối');
    }

    // Edit content
    if($action==='edit'){
        $qid=intval($input['queue_id']??0);
        $content=trim($input['content']??'');
        if(!$qid||!$content) cq_fail('Missing data');
        $d->query("UPDATE content_queue SET content=? WHERE id=?",[$content,$qid]);
        cq_ok('Đã cập nhật');
    }

    // Bulk approve
    if($action==='bulk_approve'){
        $ids=$input['queue_ids']??[];
        if(!is_array($ids)||count($ids)>20) cq_fail('1-20 items');
        $count=0;
        foreach($ids as $qid){
            $qid=intval($qid);
            $item=$d->fetchOne("SELECT * FROM content_queue WHERE id=? AND `status`='pending'",[$qid]);
            if($item){
                $pdo->prepare("INSERT INTO posts (user_id,content,type,`status`,created_at) VALUES (?,?,?,'active',NOW())")->execute([intval($item['user_id']??2),$item['content'],$item['type']??'tip']);
                $d->query("UPDATE content_queue SET `status`='published',published_at=NOW() WHERE id=?",[$qid]);
                $count++;
            }
        }
        cq_ok('Đã duyệt '.$count.' bài',['count'=>$count]);
    }

    cq_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
