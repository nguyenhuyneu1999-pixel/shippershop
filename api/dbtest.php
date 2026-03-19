<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();

$filter=$_GET['filter']??'all';
$seedMin=3;$seedMax=102;
$userAll="id>1";
$userReal="(id=2 OR id>$seedMax)";
$postAll="`status`='active'";
$postReal="`status`='active' AND (user_id=2 OR user_id>$seedMax)";
$uf=($filter==='real')?$userReal:$userAll;
$pf=($filter==='real')?$postReal:$postAll;

$totalUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf")['c'];
$realUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE $userReal")['c'];
$seedUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>=$seedMin AND id<=$seedMax")['c'];
$todayUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf AND DATE(created_at)=CURDATE()")['c'];
$totalPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf")['c'];
$realPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $postReal")['c'];
$seedPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id>=$seedMin AND user_id<=$seedMax")['c'];
$todayPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf AND DATE(created_at)=CURDATE()")['c'];
$cmtFilter=($filter==='real')?"AND (c.user_id=2 OR c.user_id>$seedMax)":"";
$totalCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments c WHERE 1=1 $cmtFilter")['c'];
$todayCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments c WHERE DATE(c.created_at)=CURDATE() $cmtFilter")['c'];
$totalLikes=$d->fetchOne("SELECT COUNT(*) as c FROM likes")['c'];
$totalGroups=$d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c'];
$totalGP=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c'];
$totalMk=$d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings WHERE `status`='active'")['c'];
$totalTraffic=$d->fetchOne("SELECT COUNT(*) as c FROM traffic_alerts")['c'];
$totalMsg=$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c'];
$totalConv=$d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c'];
$totalWallets=$d->fetchOne("SELECT COUNT(*) as c FROM wallets")['c'];
$totalBalance=$d->fetchOne("SELECT COALESCE(SUM(balance),0) as s FROM wallets")['s'];
$activeSubs=$d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active'")['c'];
$pendingDeposits=$d->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE `status`='pending'")['c'];
$pushSubs=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
$weekPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf AND created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)")['c'];
$weekUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf AND created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)")['c'];
$postsByDay=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as cnt FROM posts WHERE $pf AND created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
$topPosters=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,COUNT(p.id) as post_count FROM users u JOIN posts p ON u.id=p.user_id WHERE p.`status`='active' AND u.id>1 ".($filter==='real'?"AND (u.id=2 OR u.id>$seedMax)":"")." GROUP BY u.id ORDER BY post_count DESC LIMIT 5");

echo json_encode(['success'=>true,'data'=>[
    'filter'=>$filter,
    'users'=>['total'=>(int)$totalUsers,'real'=>(int)$realUsers,'seed'=>(int)$seedUsers,'today'=>(int)$todayUsers],
    'posts'=>['total'=>(int)$totalPosts,'real'=>(int)$realPosts,'seed'=>(int)$seedPosts,'today'=>(int)$todayPosts,'week'=>(int)$weekPosts],
    'comments'=>['total'=>(int)$totalCmts,'today'=>(int)$todayCmts],
    'likes'=>(int)$totalLikes,
    'groups'=>['total'=>(int)$totalGroups,'posts'=>(int)$totalGP],
    'marketplace'=>(int)$totalMk,
    'traffic'=>(int)$totalTraffic,
    'messages'=>['total'=>(int)$totalMsg,'conversations'=>(int)$totalConv],
    'wallet'=>['total'=>(int)$totalWallets,'balance'=>$totalBalance,'active_subs'=>(int)$activeSubs,'pending_deposits'=>(int)$pendingDeposits],
    'push_subs'=>(int)$pushSubs,
    'week_users'=>(int)$weekUsers,
    'posts_by_day'=>$postsByDay,
    'top_posters'=>$topPosters
]]);
