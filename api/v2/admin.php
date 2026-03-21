<?php
/**
 * ShipperShop API v2 — Admin Dashboard
 * ALL endpoints require admin role
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';
$uid=require_admin();

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// ========== GET ==========
if($method==='GET'){

    // --- Dashboard stats ---
    if($action==='dashboard'){
        $stats=cache_remember('admin_dashboard',function()use($d){
            $today=date('Y-m-d');
            $week=date('Y-m-d',strtotime('-7 days'));
            $month=date('Y-m-d',strtotime('-30 days'));
            return [
                'users'=>[
                    'total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']),
                    'today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND DATE(created_at)=?",[$today])['c']),
                    'week'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at>=?",[$week])['c']),
                    'online'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),
                ],
                'posts'=>[
                    'total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']),
                    'today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND DATE(created_at)=?",[$today])['c']),
                    'week'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at>=?",[$week])['c']),
                ],
                'messages'=>[
                    'total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']),
                    'today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE DATE(created_at)=?",[$today])['c']),
                ],
                'groups'=>[
                    'total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']),
                    'posts'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE `status`='active'")['c']),
                ],
                'revenue'=>[
                    'total_subs'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active'")['c']),
                    'pending_deposits'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE `status`='pending'")['c']),
                ],
                'reports'=>[
                    'pending'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']),
                ],
                'errors'=>[
                    'today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM error_logs WHERE DATE(created_at)=?",[$today])['c']),
                ],
            ];
        },60);
        ok('OK',$stats);
    }

    // --- Users list ---
    if($action==='users'){
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;
        $search=$_GET['search']??'';
        $role=$_GET['role']??'';
        $st=$_GET['status']??'';
        $company=$_GET['company']??'';

        $where=["1=1"];$params=[];
        if($search){$where[]="(u.fullname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";$params[]='%'.$search.'%';$params[]='%'.$search.'%';$params[]='%'.$search.'%';}
        if($role){$where[]="u.role=?";$params[]=$role;}
        if($st){$where[]="u.`status`=?";$params[]=$st;}
        if($company){$where[]="u.shipping_company=?";$params[]=$company;}
        $wc=implode(' AND ',$where);

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users u WHERE $wc",$params)['c']);
        $rows=$d->fetchAll("SELECT u.id,u.fullname,u.username,u.email,u.avatar,u.shipping_company,u.role,u.`status`,u.is_online,u.total_success,u.total_posts,u.banned_until,u.created_at,u.last_login FROM users u WHERE $wc ORDER BY u.id DESC LIMIT $limit OFFSET $offset",$params);
        echo json_encode(['success'=>true,'data'=>['users'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // --- Reports list ---
    if($action==='reports'){
        $st=$_GET['status']??'pending';
        $page=max(1,intval($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
        try{
            $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`=?",[$st])['c']);
            $rows=$d->fetchAll("SELECT r.id,r.post_id,r.user_id,r.reason,r.detail,r.`status`,r.created_at,r.reviewed_at,u.fullname as reporter_name,p.content as post_content,p.user_id as post_owner_id,pu.fullname as post_owner_name FROM post_reports r LEFT JOIN users u ON r.user_id=u.id LEFT JOIN posts p ON r.post_id=p.id LEFT JOIN users pu ON p.user_id=pu.id WHERE r.`status`=? ORDER BY r.created_at DESC LIMIT $limit OFFSET $offset",[$st]);
        }catch(\Throwable $e){
            $total=0;$rows=[];
        }
        echo json_encode(['success'=>true,'data'=>['reports'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total]]]);exit;
    }

    // --- Deposits list ---
    if($action==='deposits'){
        $st=$_GET['status']??'pending';
        try{$rows=$d->fetchAll("SELECT wt.*,u.fullname,u.email FROM wallet_transactions wt LEFT JOIN users u ON wt.user_id=u.id WHERE wt.type='deposit' AND wt.`status`=? ORDER BY wt.created_at DESC LIMIT 50",[$st]);}catch(\Throwable $e){$rows=[];}
        ok('OK',$rows);
    }

    // --- Error logs ---
    if($action==='errors'){
        $level=$_GET['level']??'';
        $limit=min(intval($_GET['limit']??50),100);
        $w="1=1";$p=[];
        if($level){$w="level=?";$p[]=$level;}
        $rows=$d->fetchAll("SELECT id,level,message,file,line,url,ip,created_at FROM error_logs WHERE $w ORDER BY created_at DESC LIMIT $limit",$p);
        ok('OK',$rows);
    }

    // --- Analytics ---
    if($action==='analytics'){
        $days=min(intval($_GET['days']??7),90);
        // User growth per day
        $ug=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        // Post activity per day
        $pa=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        // Page views per day
        $pv=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM page_views WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        // Top pages
        $tp=$d->fetchAll("SELECT page,COUNT(*) as views FROM page_views WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY page ORDER BY views DESC LIMIT 10");
        // Engagement (likes + comments per day)
        $eng=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM likes WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        $cmt=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM comments WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        // Shipping company breakdown
        $companies=$d->fetchAll("SELECT shipping_company as name,COUNT(*) as count FROM users WHERE shipping_company IS NOT NULL AND shipping_company!='' AND `status`='active' GROUP BY shipping_company ORDER BY count DESC LIMIT 15");
        // Active users (posted or liked in last N days)
        $activeUsers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM (SELECT user_id FROM posts WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) UNION SELECT user_id FROM likes WHERE created_at>=DATE_SUB(NOW(),INTERVAL $days DAY)) x")['c']);
        // Subscription revenue
        $revenue=$d->fetchAll("SELECT DATE(created_at) as day,SUM(amount) as total FROM wallet_transactions WHERE type='subscription' AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
        ok('OK',['user_growth'=>$ug,'post_activity'=>$pa,'page_views'=>$pv,'top_pages'=>$tp,'engagement_likes'=>$eng,'engagement_comments'=>$cmt,'companies'=>$companies,'active_users'=>$activeUsers,'revenue'=>$revenue,'period_days'=>$days]);
    }

    // --- System info ---
    if($action==='system'){
        $dbSize=$d->fetchOne("SELECT SUM(data_length+index_length) as s FROM information_schema.tables WHERE table_schema=DATABASE()");
        $tables=$d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
        $diskFree=function_exists('disk_free_space')?disk_free_space('/'):0;
        $diskTotal=function_exists('disk_total_space')?disk_total_space('/'):1;
        ok('OK',[
            'php_version'=>PHP_VERSION,
            'db_size_mb'=>round(intval($dbSize['s']??0)/1024/1024,2),
            'db_tables'=>intval($tables['c']??0),
            'disk_free_mb'=>round($diskFree/1048576),
            'disk_used_pct'=>round((1-$diskFree/$diskTotal)*100,1),
            'server_time'=>date('Y-m-d H:i:s'),
            'uptime'=>@file_get_contents('/proc/uptime')?trim(explode(' ',file_get_contents('/proc/uptime'))[0]).'s':'N/A',
            'total_users'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']),
            'total_posts'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']),
            'total_groups'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM groups")['c']),
            'active_stories'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM stories WHERE expires_at>NOW()")['c']),
            'pending_reports'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']),
            'pending_deposits'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']),
        ]);
    }

    ok('OK',[]);
}

// ========== POST ==========
if($method==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Ban user
    if($action==='ban_user'){
        $tid=intval($input['user_id']??0);$reason=trim($input['reason']??'Vi phạm quy tắc');
        $days=max(1,intval($input['days']??7));
        $until=date('Y-m-d H:i:s',time()+$days*86400);
        $d->query("UPDATE users SET banned_until=?,ban_reason=? WHERE id=?",[$until,$reason,$tid]);
        ok('Đã khóa '.($days).' ngày');
    }
    if($action==='unban_user'){
        $tid=intval($input['user_id']??0);
        $d->query("UPDATE users SET banned_until=NULL,ban_reason=NULL WHERE id=?",[$tid]);
        ok('Đã mở khóa');
    }

    // Delete/hide post
    if($action==='delete_post'){
        $pid=intval($input['post_id']??0);
        $d->query("UPDATE posts SET `status`='deleted' WHERE id=?",[$pid]);
        ok('Đã xóa');
    }
    if($action==='hide_post'){
        $pid=intval($input['post_id']??0);
        $d->query("UPDATE posts SET `status`='hidden' WHERE id=?",[$pid]);
        ok('Đã ẩn');
    }

    // Resolve report
    if($action==='resolve_report'){
        $rid=intval($input['report_id']??0);
        $resolution=$input['resolution']??'resolved';
        if(!in_array($resolution,['resolved','dismissed'])) $resolution='resolved';
        $d->query("UPDATE post_reports SET `status`=?,reviewer_id=?,reviewed_at=NOW() WHERE id=?",[$resolution,$uid,$rid]);
        // If resolved, hide the post
        if($resolution==='resolved'){
            $report=$d->fetchOne("SELECT post_id FROM post_reports WHERE id=?",[$rid]);
            if($report) $d->query("UPDATE posts SET `status`='hidden' WHERE id=?",[$report['post_id']]);
        }
        ok('OK');
    }

    // Approve/reject deposit
    if($action==='approve_deposit'){
        $txid=intval($input['transaction_id']??0);
        $tx=$d->fetchOne("SELECT user_id,amount FROM wallet_transactions WHERE id=? AND type='deposit' AND `status`='pending'",[$txid]);
        if(!$tx) fail('Không tìm thấy');
        $d->query("UPDATE wallet_transactions SET `status`='completed' WHERE id=?",[$txid]);
        $d->query("UPDATE wallets SET balance=balance+? WHERE user_id=?",[$tx['amount'],$tx['user_id']]);
        try{$d->query("INSERT INTO audit_log (user_id,action,details,created_at) VALUES (?,'approve_deposit',?,NOW())",[$uid,json_encode(['tx_id'=>$txid,'amount'=>$tx['amount'],'target'=>$tx['user_id']])]);}catch(\Throwable $e){}
        ok('Đã duyệt');
    }
    if($action==='reject_deposit'){
        $txid=intval($input['transaction_id']??0);
        $d->query("UPDATE wallet_transactions SET `status`='rejected' WHERE id=? AND type='deposit'",[$txid]);
        ok('Đã từ chối');
    }

    // Set user role
    if($action==='set_role'){
        $tid=intval($input['user_id']??0);$role=$input['role']??'user';
        if(!in_array($role,['user','admin','moderator'])) fail('Role không hợp lệ');
        $d->query("UPDATE users SET role=? WHERE id=?",[$role,$tid]);
        ok('OK');
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
