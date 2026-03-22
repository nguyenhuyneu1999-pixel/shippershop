<?php
// ShipperShop API v2 — Pin Posts (to profile or group)
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

function pn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pn_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get pinned posts for a user's profile
    if($action==='profile'){
        $userId=intval($_GET['user_id']??0);
        if(!$userId) pn_fail('Missing user_id');
        $pins=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM pinned_posts pp JOIN posts p ON pp.post_id=p.id LEFT JOIN users u ON p.user_id=u.id WHERE pp.pinned_by=? AND pp.pin_type='profile' AND p.`status`='active' ORDER BY pp.created_at DESC LIMIT 3",[$userId]);
        pn_ok('OK',$pins);
    }
    // Get pinned posts for a group
    if($action==='group'){
        $groupId=intval($_GET['group_id']??0);
        if(!$groupId) pn_fail('Missing group_id');
        $pins=$d->fetchAll("SELECT gp.*,u.fullname as user_name,u.avatar as user_avatar FROM pinned_posts pp JOIN group_posts gp ON pp.post_id=gp.id LEFT JOIN users u ON gp.user_id=u.id WHERE pp.pin_type='group_'.$groupId AND gp.`status`='active' ORDER BY pp.created_at DESC LIMIT 3");
        pn_ok('OK',$pins);
    }
    pn_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Pin/unpin toggle
    if($action==='toggle'){
        $postId=intval($input['post_id']??0);
        $pinType=$input['pin_type']??'profile';
        if(!$postId) pn_fail('Missing post_id');

        // Verify ownership
        if($pinType==='profile'){
            $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$postId]);
            if(!$post||intval($post['user_id'])!==$uid) pn_fail('Chỉ ghim bài viết của bạn',403);
        }

        $existing=$d->fetchOne("SELECT id FROM pinned_posts WHERE post_id=? AND pin_type=?",[$postId,$pinType]);
        if($existing){
            $d->query("DELETE FROM pinned_posts WHERE post_id=? AND pin_type=?",[$postId,$pinType]);
            pn_ok('Đã bỏ ghim',['pinned'=>false]);
        }else{
            // Max 3 pins per profile
            $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM pinned_posts WHERE pinned_by=? AND pin_type=?",[$uid,$pinType])['c']);
            if($count>=3) pn_fail('Tối đa 3 bài ghim');
            $pdo->prepare("INSERT INTO pinned_posts (post_id,pinned_by,pin_type,created_at) VALUES (?,?,?,NOW())")->execute([$postId,$uid,$pinType]);
            pn_ok('Đã ghim!',['pinned'=>true]);
        }
    }

    pn_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
