<?php
// ShipperShop API v2 — Content Moderation Queue
// Report, review, action on posts/users/comments
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

function mod_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mod_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Queue — admin only
    if($action==='queue'){
        $uid=require_auth();
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') mod_fail('Admin only',403);

        $status=$_GET['status']??'pending';
        $limit=min(intval($_GET['limit']??30),100);

        $reports=$d->fetchAll("SELECT pr.*,
            u.fullname as reporter_name,u.avatar as reporter_avatar,
            p.content as post_content,p.user_id as post_author_id,
            au.fullname as post_author_name
            FROM post_reports pr
            LEFT JOIN users u ON pr.reporter_id=u.id
            LEFT JOIN posts p ON pr.post_id=p.id
            LEFT JOIN users au ON p.user_id=au.id
            WHERE pr.`status`=?
            ORDER BY pr.created_at DESC LIMIT $limit",[$status]);

        // Stats
        $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
        $resolved=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`!='pending'")['c']);

        mod_ok('OK',['reports'=>$reports,'stats'=>['pending'=>$pending,'resolved'=>$resolved]]);
    }

    // Report reasons (for UI)
    if($action==='reasons'){
        mod_ok('OK',[
            ['id'=>'spam','label'=>'Spam / Quảng cáo'],
            ['id'=>'inappropriate','label'=>'Nội dung không phù hợp'],
            ['id'=>'harassment','label'=>'Quấy rối / Bắt nạt'],
            ['id'=>'misinformation','label'=>'Thông tin sai lệch'],
            ['id'=>'violence','label'=>'Bạo lực / Đe dọa'],
            ['id'=>'scam','label'=>'Lừa đảo'],
            ['id'=>'copyright','label'=>'Vi phạm bản quyền'],
            ['id'=>'other','label'=>'Lý do khác'],
        ]);
    }

    mod_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Report post
    if($action==='report'){
        $postId=intval($input['post_id']??0);
        $reason=trim($input['reason']??'');
        $detail=trim($input['detail']??'');
        if(!$postId) mod_fail('Missing post_id');
        if(!$reason) mod_fail('Chọn lý do báo cáo');

        // Check duplicate
        $exists=$d->fetchOne("SELECT id FROM post_reports WHERE post_id=? AND reporter_id=? AND `status`='pending'",[$postId,$uid]);
        if($exists) mod_fail('Bạn đã báo cáo bài này rồi');

        $pdo->prepare("INSERT INTO post_reports (post_id,reporter_id,reason,detail,`status`,created_at) VALUES (?,?,?,?,'pending',NOW())")->execute([$postId,$uid,$reason,$detail]);

        // Auto-hide if >= 3 pending reports
        $reportCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE post_id=? AND `status`='pending'",[$postId])['c']);
        if($reportCount>=3){
            $d->query("UPDATE posts SET `status`='hidden' WHERE id=? AND `status`='active'",[$postId]);
        }

        // Notify admin
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (2,'moderation','Báo cáo mới',?,?,NOW())")->execute(['Post #'.$postId.' — '.$reason,json_encode(['post_id'=>$postId,'reporter_id'=>$uid])]);}catch(\Throwable $e){}

        mod_ok('Đã báo cáo! Admin sẽ xem xét.');
    }

    // Admin: resolve report
    if($action==='resolve'){
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') mod_fail('Admin only',403);

        $reportId=intval($input['report_id']??0);
        $resolution=$input['resolution']??''; // dismiss, hide, delete, ban_user
        if(!$reportId||!$resolution) mod_fail('Missing data');

        $report=$d->fetchOne("SELECT * FROM post_reports WHERE id=?",[$reportId]);
        if(!$report) mod_fail('Report not found',404);

        // Apply action
        $postId=intval($report['post_id']);
        if($resolution==='hide'){
            $d->query("UPDATE posts SET `status`='hidden' WHERE id=?",[$postId]);
        }elseif($resolution==='delete'){
            $d->query("UPDATE posts SET `status`='deleted' WHERE id=?",[$postId]);
        }elseif($resolution==='ban_user'){
            $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$postId]);
            if($post){
                $d->query("UPDATE users SET `status`='banned' WHERE id=?",[$post['user_id']]);
                // Notify banned user
                try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'system','Tài khoản bị khóa','Tài khoản đã bị khóa do vi phạm quy tắc cộng đồng','{}',NOW())")->execute([$post['user_id']]);}catch(\Throwable $e){}
            }
        }

        // Mark report resolved
        $d->query("UPDATE post_reports SET `status`=?,resolved_by=?,resolved_at=NOW() WHERE id=?",[$resolution,$uid,$reportId]);
        // Also resolve other reports for same post
        $d->query("UPDATE post_reports SET `status`=?,resolved_by=?,resolved_at=NOW() WHERE post_id=? AND `status`='pending' AND id!=?",[$resolution,$uid,$postId,$reportId]);

        // Audit
        try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'moderation',?,?,NOW())")->execute([$uid,'Report #'.$reportId.' → '.$resolution,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

        mod_ok('Đã xử lý: '.$resolution);
    }

    mod_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
