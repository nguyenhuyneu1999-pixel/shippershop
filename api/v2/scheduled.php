<?php
// ShipperShop API v2 — Scheduled Posts & Drafts
// session removed: JWT auth only
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

function sc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();

    // My scheduled posts
    if(!$action||$action==='list'){
        $type=$_GET['type']??'all'; // all, scheduled, draft
        $w=["user_id=?"];$p=[$uid];
        if($type==='scheduled'){$w[]="scheduled_at IS NOT NULL AND is_draft=0 AND `status`='active'";}
        elseif($type==='draft'){$w[]="is_draft=1";}
        else{$w[]="(scheduled_at IS NOT NULL OR is_draft=1)";}
        $wc=implode(' AND ',$w);
        $posts=$d->fetchAll("SELECT id,content,type,province,district,scheduled_at,is_draft,created_at FROM posts WHERE $wc ORDER BY COALESCE(scheduled_at,created_at) DESC LIMIT 50",$p);
        sc_ok('OK',$posts);
    }

    sc_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create scheduled post
    if($action==='create'){
        $content=trim($input['content']??'');
        if(mb_strlen($content)<3) sc_fail('Nội dung tối thiểu 3 ký tự');
        $scheduledAt=$input['scheduled_at']??null;
        $isDraft=intval($input['is_draft']??0);

        if($scheduledAt){
            $ts=strtotime($scheduledAt);
            if(!$ts||$ts<time()+300) sc_fail('Thời gian phải sau ít nhất 5 phút');
            $scheduledAt=date('Y-m-d H:i:s',$ts);
        }

        $province=trim($input['province']??'');
        $district=trim($input['district']??'');
        $type=$input['type']??'post';

        // Draft = hidden, scheduled = hidden until time
        $status=$isDraft?'draft':'active';

        $pdo->prepare("INSERT INTO posts (user_id,content,type,province,district,`status`,scheduled_at,is_draft,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())")->execute([$uid,$content,$type,$province,$district,$status,$scheduledAt,$isDraft]);
        $id=intval($pdo->lastInsertId());
        if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM posts");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        $msg=$isDraft?'Đã lưu nháp':'Đã hẹn giờ đăng';
        sc_ok($msg,['id'=>$id,'scheduled_at'=>$scheduledAt,'is_draft'=>$isDraft]);
    }

    // Edit scheduled/draft
    if($action==='edit'){
        $pid=intval($input['post_id']??0);
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
        if(!$post||intval($post['user_id'])!==$uid) sc_fail('Không có quyền',403);
        $fields=[];$params=[];
        if(isset($input['content'])){$fields[]="content=?";$params[]=trim($input['content']);}
        if(isset($input['scheduled_at'])){
            $ts=strtotime($input['scheduled_at']);
            $fields[]="scheduled_at=?";$params[]=$ts?date('Y-m-d H:i:s',$ts):null;
        }
        if(isset($input['is_draft'])){$fields[]="is_draft=?";$params[]=intval($input['is_draft']);}
        if($fields){$params[]=$pid;$d->query("UPDATE posts SET ".implode(',',$fields)." WHERE id=?",$params);}
        sc_ok('Đã cập nhật');
    }

    // Publish draft now
    if($action==='publish_now'){
        $pid=intval($input['post_id']??0);
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
        if(!$post||intval($post['user_id'])!==$uid) sc_fail('Không có quyền',403);
        $d->query("UPDATE posts SET `status`='active',is_draft=0,scheduled_at=NULL,created_at=NOW() WHERE id=?",[$pid]);
        sc_ok('Đã đăng!',['id'=>$pid]);
    }

    // Delete scheduled/draft
    if($action==='delete'){
        $pid=intval($input['post_id']??0);
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
        if(!$post||intval($post['user_id'])!==$uid) sc_fail('Không có quyền',403);
        $d->query("UPDATE posts SET `status`='deleted' WHERE id=?",[$pid]);
        sc_ok('Đã xóa');
    }

    sc_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
