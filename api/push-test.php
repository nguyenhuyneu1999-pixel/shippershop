<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== REAL DATABASE STATS ===\n\n";

// Users
$totalUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>1")['c'];
// Real users = id 2 (admin) + anyone registered after seed
// Fake users were seeded with IDs 3-102 (100 users)
$realUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>1 AND (id=2 OR id>102)")['c'];
$fakeUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>=3 AND id<=102")['c'];
echo "Users total: $totalUsers (real: $realUsers, fake/seed: $fakeUsers)\n";

// Posts
$totalPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c'];
// Seed posts were created with specific patterns - check user_id
$realPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND (user_id=2 OR user_id>102)")['c'];
$fakePosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id>=3 AND user_id<=102")['c'];
echo "Posts total: $totalPosts (real: $realPosts, fake/seed: $fakePosts)\n";

// Comments
$totalCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments")['c'];
$realCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE (user_id=2 OR user_id>102)")['c'];
echo "Comments total: $totalCmts (real: $realCmts)\n";

// Likes  
$totalLikes=$d->fetchOne("SELECT COUNT(*) as c FROM likes")['c'];
echo "Likes total: $totalLikes\n";

// Groups
$totalGroups=$d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c'];
echo "Groups: $totalGroups\n";

// Group posts
$totalGP=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c'];
echo "Group posts: $totalGP\n";

// Marketplace
$totalMk=$d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings WHERE `status`='active'")['c'];
echo "Marketplace: $totalMk\n";

// Traffic
$totalTr=$d->fetchOne("SELECT COUNT(*) as c FROM traffic_alerts")['c'];
echo "Traffic alerts: $totalTr\n";

// Messages
$totalMsg=$d->fetchOne("SELECT COUNT(*) as c FROM messages")['c'];
echo "Messages: $totalMsg\n";

// Conversations
$totalConv=$d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c'];
echo "Conversations: $totalConv\n";

// Wallet
$totalWallets=$d->fetchOne("SELECT COUNT(*) as c FROM wallets")['c'];
$totalBalance=$d->fetchOne("SELECT COALESCE(SUM(balance),0) as s FROM wallets")['s'];
echo "Wallets: $totalWallets (total balance: $totalBalance)\n";

// Subscriptions
$totalSubs=$d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active'")['c'];
echo "Active subscriptions: $totalSubs\n";

// Push subscriptions
$pushSubs=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
echo "Push subscriptions: $pushSubs\n";

// Today's activity
$todayPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE DATE(created_at)=CURDATE()")['c'];
$todayUsers=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE DATE(created_at)=CURDATE() AND id>1")['c'];
$todayCmts=$d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE DATE(created_at)=CURDATE()")['c'];
echo "\nToday: posts=$todayPosts, users=$todayUsers, comments=$todayCmts\n";

// Max user ID to determine seed boundary
$maxReal=$d->fetchOne("SELECT MAX(id) as m FROM users WHERE id>102")['m'];
echo "Max real user ID: ".($maxReal??'none')."\n";
echo "DONE\n";
