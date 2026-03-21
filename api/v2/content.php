<?php
/**
 * ShipperShop API v2 — Content Management
 * Content queue, scheduled posts, auto-publish management
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/validator.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $u=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$u||$u['role']!=='admin') fail('Admin only',403);

    // Queue list
    if($action==='queue'||!$action){
        $status=$_GET['status']??'';$page=max(1,intval($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
        $w=["1=1"];$p=[];
        if($status){$w[]="`status`=?";$p[]=$status;}
        $wc=implode(' AND ',$w);
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE $wc",$p)['c']);
        $rows=$d->fetchAll("SELECT cq.*,u.fullname as user_name FROM content_queue cq LEFT JOIN users u ON cq.user_id=u.id WHERE $wc ORDER BY cq.scheduled_at DESC LIMIT $limit OFFSET $offset",$p);
        echo json_encode(['success'=>true,'data'=>['items'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // Stats
    if($action==='stats'){
        $stats=[
            'total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue")['c']),
            'pending'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='pending'")['c']),
            'published'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published'")['c']),
            'failed'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='failed'")['c']),
            'today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published' AND DATE(updated_at)=CURDATE()")['c']),
        ];
        ok('OK',$stats);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $u=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$u||$u['role']!=='admin') fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);

    // Add to queue
    if($action==='add'){
        $content=trim($input['content']??'');$targetUser=intval($input['user_id']??2);
        $type=$input['type']??'post';$province=$input['province']??'';
        $district=$input['district']??'';$ward=$input['ward']??'';
        $scheduledAt=$input['scheduled_at']??date('Y-m-d H:i:s',strtotime('+1 hour'));
        if(!$content) fail('Content trống');
        $pdo->prepare("INSERT INTO content_queue (user_id,content,type,province,district,ward,`status`,scheduled_at,created_at) VALUES (?,?,?,?,?,?,'pending',?,NOW())")->execute([$targetUser,$content,$type,$province,$district,$ward,$scheduledAt]);
        $id=intval($pdo->lastInsertId());
        ok('Đã thêm vào queue',['id'=>$id]);
    }

    // Publish now (skip schedule)
    if($action==='publish_now'){
        $qid=intval($input['id']??0);
        $item=$d->fetchOne("SELECT * FROM content_queue WHERE id=?",[$qid]);
        if(!$item) fail('Không tìm thấy');
        $pdo->prepare("INSERT INTO posts (user_id,content,type,province,district,ward,`status`,created_at) VALUES (?,?,?,?,?,?,'active',NOW())")->execute([
            $item['user_id'],$item['content'],$item['type']??'post',$item['province']??'',$item['district']??'',$item['ward']??''
        ]);
        $d->query("UPDATE content_queue SET `status`='published',updated_at=NOW() WHERE id=?",[$qid]);
        ok('Đã đăng');
    }

    // Delete from queue
    if($action==='delete'){
        $qid=intval($input['id']??0);
        $d->query("DELETE FROM content_queue WHERE id=?",[$qid]);
        ok('Đã xóa');
    }

    // Update queue item
    if($action==='update'){
        $qid=intval($input['id']??0);
        $fields=[];$params=[];
        if(isset($input['content'])){$fields[]="content=?";$params[]=trim($input['content']);}
        if(isset($input['scheduled_at'])){$fields[]="scheduled_at=?";$params[]=$input['scheduled_at'];}
        if(isset($input['status'])){$fields[]="`status`=?";$params[]=$input['status'];}
        if(!empty($fields)){$params[]=$qid;$d->query("UPDATE content_queue SET ".implode(',',$fields).",updated_at=NOW() WHERE id=?",$params);}
        ok('Đã cập nhật');
    }

    // Bulk publish
    if($action==='bulk_publish'){
        $ids=$input['ids']??[];
        if(!is_array($ids)||empty($ids)) fail('Missing ids');
        $count=0;
        foreach($ids as $qid){
            $item=$d->fetchOne("SELECT * FROM content_queue WHERE id=? AND `status`='pending'",[$qid]);
            if(!$item) continue;
            try{
                $pdo->prepare("INSERT INTO posts (user_id,content,type,province,district,ward,`status`,created_at) VALUES (?,?,?,?,?,?,'active',NOW())")->execute([
                    $item['user_id'],$item['content'],$item['type']??'post',$item['province']??'',$item['district']??'',$item['ward']??''
                ]);
                $d->query("UPDATE content_queue SET `status`='published',updated_at=NOW() WHERE id=?",[$qid]);
                $count++;
            }catch(\Throwable $e){}
        }
        ok('Published '.$count.' items',['count'=>$count]);
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
