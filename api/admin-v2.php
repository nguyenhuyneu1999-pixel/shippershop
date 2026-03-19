<?php
/**
 * ShipperShop Admin API v2
 * Comprehensive management for up to 1M users
 * All endpoints require admin auth (user_id=2)
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';

header('Content-Type: application/json; charset=utf-8');
$d = db();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Admin check
$uid = getOptionalAuthUserId();
if (!$uid || $uid !== 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function ok($data = null, $msg = 'OK') {
    echo json_encode(['success' => true, 'message' => $msg, 'data' => $data]);
    exit;
}
function err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function input() {
    return json_decode(file_get_contents('php://input'), true) ?: $_POST;
}

// ============================================================
// DASHBOARD
// ============================================================
if ($action === 'dashboard') {
    $users = $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c'];
    $usersToday = $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE DATE(created_at)=CURDATE()")['c'];
    $usersWeek = $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'];
    $posts = $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c'];
    $postsToday = $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE DATE(created_at)=CURDATE()")['c'];
    $comments = $d->fetchOne("SELECT COUNT(*) as c FROM comments")['c'];
    $groups = $d->fetchOne("SELECT COUNT(*) as c FROM `groups` WHERE `status`='active'")['c'];
    $groupPosts = $d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c'];
    $listings = $d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings WHERE `status`='active'")['c'];
    $messages = $d->fetchOne("SELECT COUNT(*) as c FROM messages")['c'];
    $traffic = $d->fetchOne("SELECT COUNT(*) as c FROM traffic_alerts")['c'];
    $follows = $d->fetchOne("SELECT COUNT(*) as c FROM follows")['c'];
    $pushSubs = $d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];

    // Revenue
    $revenue = $d->fetchOne("SELECT COALESCE(SUM(amount),0) as total FROM wallet_transactions WHERE type='subscription' AND `status`='completed'")['total'];

    // Growth chart (last 30 days)
    $growth = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date");

    // Active users (posted/commented in last 7 days)
    $active7d = $d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM (
        SELECT user_id FROM posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT user_id FROM comments WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ) t")['c'];

    // Page views
    $views7d = 0;
    $viewsToday = 0;
    try {
        $views7d = $d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'];
        $viewsToday = $d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE DATE(created_at)=CURDATE()")['c'];
    } catch(Throwable $e) {}

    ok([
        'users' => ['total' => (int)$users, 'today' => (int)$usersToday, 'week' => (int)$usersWeek, 'active_7d' => (int)$active7d],
        'content' => ['posts' => (int)$posts, 'posts_today' => (int)$postsToday, 'comments' => (int)$comments, 'group_posts' => (int)$groupPosts],
        'community' => ['groups' => (int)$groups, 'follows' => (int)$follows, 'messages' => (int)$messages],
        'marketplace' => ['listings' => (int)$listings],
        'traffic_alerts' => (int)$traffic,
        'push_subs' => (int)$pushSubs,
        'revenue' => (int)$revenue,
        'views' => ['today' => (int)$viewsToday, 'week' => (int)$views7d],
        'growth' => $growth
    ]);
}

// ============================================================
// USERS MANAGEMENT
// ============================================================
if ($action === 'users') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';

    $where = ["u.id > 1"]; // skip id=1
    $params = [];
    
    // Real user detection: registered via web form or admin
    $realUserCond = "(u.email LIKE '%@shippershop.local' OR u.email = 'nguyenhuyneu1999@gmail.com' OR u.email = 'nguyenvanhuy12123@gmail.com')";
    
    if ($search) {
        $where[] = "(u.fullname LIKE ? OR u.username LIKE ? OR u.id = ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = intval($search);
    }
    if ($status && in_array($status, ['active','banned','suspended'])) {
        $where[] = "u.`status` = ?";
        $params[] = $status;
    }
    
    // Type filter: real / seed
    $type = trim($_GET['type'] ?? '');
    if ($type === 'real') {
        $where[] = $realUserCond;
    } elseif ($type === 'seed') {
        $where[] = "NOT $realUserCond";
    }

    $orderBy = 'u.id DESC';
    if ($sort === 'oldest') $orderBy = 'u.id ASC';
    elseif ($sort === 'name') $orderBy = 'u.fullname ASC';
    elseif ($sort === 'most_posts') $orderBy = 'post_count DESC';

    $whereStr = implode(' AND ', $where);
    $total = $d->fetchOne("SELECT COUNT(*) as c FROM users u WHERE $whereStr", $params)['c'];

    $users = $d->fetchAll("SELECT u.id, u.fullname, u.username, u.email, u.avatar, u.shipping_company,
        u.`status`, u.created_at, u.ref_code,
        (SELECT COUNT(*) FROM posts WHERE user_id=u.id) as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id=u.id) as comment_count,
        (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as follower_count,
        CASE WHEN $realUserCond THEN 0 ELSE 1 END as is_seed
        FROM users u WHERE $whereStr ORDER BY $orderBy LIMIT $limit OFFSET $offset", $params);

    ok(['users' => $users, 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'user_detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) err('Missing id');
    $user = $d->fetchOne("SELECT u.*, 
        (SELECT COUNT(*) FROM posts WHERE user_id=u.id) as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id=u.id) as comment_count,
        (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as follower_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id=u.id) as following_count,
        (SELECT COUNT(*) FROM likes WHERE user_id=u.id) as like_count
        FROM users u WHERE u.id=?", [$id]);
    if (!$user) err('User not found', 404);
    unset($user['password']);
    ok($user);
}

if ($action === 'user_update' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    if (!$id) err('Missing id');
    $allowed = ['fullname','username','shipping_company','status'];
    $sets = [];
    $params = [];
    foreach ($allowed as $f) {
        if (isset($in[$f])) {
            if ($f === 'status') {
                $sets[] = "`status` = ?";
            } else {
                $sets[] = "$f = ?";
            }
            $params[] = $in[$f];
        }
    }
    if (empty($sets)) err('Nothing to update');
    $params[] = $id;
    $d->query("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    ok(null, 'User updated');
}

if ($action === 'user_ban' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    if (!$id || $id === 2) err('Invalid');
    $d->query("UPDATE users SET `status` = 'banned' WHERE id = ?", [$id]);
    $d->query("UPDATE posts SET `status` = 'hidden' WHERE user_id = ?", [$id]);
    ok(null, 'User banned');
}

if ($action === 'user_unban' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    $d->query("UPDATE users SET `status` = 'active' WHERE id = ?", [$id]);
    ok(null, 'User unbanned');
}

if ($action === 'user_delete' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    if (!$id || $id === 2) err('Invalid');
    $d->query("UPDATE users SET `status` = 'deleted', username = CONCAT('deleted_',id) WHERE id = ?", [$id]);
    $d->query("UPDATE posts SET `status` = 'deleted' WHERE user_id = ?", [$id]);
    ok(null, 'User deleted');
}

// ============================================================
// POSTS MANAGEMENT
// ============================================================
if ($action === 'posts') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? 'active';
    $reported = $_GET['reported'] ?? '';

    $where = [];
    $params = [];
    
    // Real user detection
    $realUserCond = "(u.email LIKE '%@shippershop.local' OR u.email = 'nguyenhuyneu1999@gmail.com' OR u.email = 'nguyenvanhuy12123@gmail.com')";
    
    if ($status) { $where[] = "p.`status` = ?"; $params[] = $status; }
    if ($search) { $where[] = "(p.content LIKE ? OR u.fullname LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    
    // Type filter: real / seed
    $type = trim($_GET['type'] ?? '');
    if ($type === 'real') { $where[] = $realUserCond; }
    elseif ($type === 'seed') { $where[] = "NOT $realUserCond"; }

    $whereStr = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $total = $d->fetchOne("SELECT COUNT(*) as c FROM posts p JOIN users u ON p.user_id=u.id $whereStr", $params)['c'];

    $posts = $d->fetchAll("SELECT p.id, p.user_id, LEFT(p.content,150) as content, p.images, p.type,
        p.likes_count, p.comments_count, p.shares_count, p.`status`, p.created_at,
        u.fullname as user_name, u.avatar as user_avatar,
        CASE WHEN $realUserCond THEN 0 ELSE 1 END as is_seed
        FROM posts p JOIN users u ON p.user_id=u.id $whereStr
        ORDER BY p.id DESC LIMIT $limit OFFSET $offset", $params);

    ok(['posts' => $posts, 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'post_update' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    $status = $in['status'] ?? '';
    if (!$id || !in_array($status, ['active','hidden','deleted'])) err('Invalid');
    $d->query("UPDATE posts SET `status` = ? WHERE id = ?", [$status, $id]);
    ok(null, 'Post updated');
}

if ($action === 'post_delete' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    if (!$id) err('Missing id');
    $d->query("UPDATE posts SET `status` = 'deleted' WHERE id = ?", [$id]);
    ok(null, 'Post deleted');
}

// ============================================================
// GROUPS MANAGEMENT
// ============================================================
if ($action === 'groups_list') {
    $groups = $d->fetchAll("SELECT g.*, 
        (SELECT COUNT(*) FROM group_members WHERE group_id=g.id) as member_count,
        (SELECT COUNT(*) FROM group_posts WHERE group_id=g.id) as post_count
        FROM `groups` g ORDER BY g.id");
    ok($groups);
}

if ($action === 'group_update' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    if (!$id) err('Missing id');
    $allowed = ['name','description','status'];
    $sets = [];
    $params = [];
    foreach ($allowed as $f) {
        if (isset($in[$f])) {
            $sets[] = ($f === 'status' ? "`status`" : $f) . " = ?";
            $params[] = $in[$f];
        }
    }
    if (empty($sets)) err('Nothing to update');
    $params[] = $id;
    $d->query("UPDATE `groups` SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    ok(null, 'Group updated');
}

// ============================================================
// MARKETPLACE MANAGEMENT
// ============================================================
if ($action === 'listings') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';

    $where = [];
    $params = [];
    if ($status) { $where[] = "l.`status` = ?"; $params[] = $status; }
    $whereStr = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = $d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings l $whereStr", $params)['c'];
    $items = $d->fetchAll("SELECT l.*, u.fullname as seller_name
        FROM marketplace_listings l JOIN users u ON l.user_id=u.id $whereStr
        ORDER BY l.id DESC LIMIT $limit OFFSET $offset", $params);

    ok(['listings' => $items, 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'listing_update' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    $status = $in['status'] ?? '';
    if (!$id || !in_array($status, ['active','hidden','sold','deleted'])) err('Invalid');
    $d->query("UPDATE marketplace_listings SET `status` = ? WHERE id = ?", [$status, $id]);
    ok(null, 'Listing updated');
}

// ============================================================
// WALLET & FINANCIAL
// ============================================================
if ($action === 'wallet_overview') {
    $totalBalance = $d->fetchOne("SELECT COALESCE(SUM(balance),0) as t FROM wallets")['t'];
    $pendingDeposits = $d->fetchAll("SELECT wt.*, u.fullname FROM wallet_transactions wt JOIN users u ON wt.user_id=u.id WHERE wt.type='deposit' AND wt.`status`='pending' ORDER BY wt.created_at DESC");
    $recentTxns = $d->fetchAll("SELECT wt.*, u.fullname FROM wallet_transactions wt JOIN users u ON wt.user_id=u.id ORDER BY wt.created_at DESC LIMIT 20");
    $subscribers = $d->fetchAll("SELECT us.*, u.fullname, sp.name as plan_name FROM user_subscriptions us JOIN users u ON us.user_id=u.id JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.`status`='active' ORDER BY us.created_at DESC LIMIT 20");

    ok([
        'total_balance' => (int)$totalBalance,
        'pending_deposits' => $pendingDeposits,
        'recent_transactions' => $recentTxns,
        'active_subscribers' => $subscribers
    ]);
}

if ($action === 'approve_deposit' && $method === 'POST') {
    $in = input();
    $txnId = intval($in['id'] ?? 0);
    if (!$txnId) err('Missing id');

    $txn = $d->fetchOne("SELECT * FROM wallet_transactions WHERE id=? AND type='deposit' AND `status`='pending'", [$txnId]);
    if (!$txn) err('Transaction not found');

    $d->beginTransaction();
    try {
        $d->query("UPDATE wallet_transactions SET `status`='completed' WHERE id=?", [$txnId]);
        $d->query("UPDATE wallets SET balance = balance + ? WHERE user_id = ?", [$txn['amount'], $txn['user_id']]);
        $d->commit();
        ok(null, 'Deposit approved');
    } catch(Throwable $e) {
        $d->rollback();
        err('Failed: ' . $e->getMessage());
    }
}

if ($action === 'reject_deposit' && $method === 'POST') {
    $in = input();
    $txnId = intval($in['id'] ?? 0);
    if (!$txnId) err('Missing id');
    $d->query("UPDATE wallet_transactions SET `status`='rejected' WHERE id=? AND type='deposit' AND `status`='pending'", [$txnId]);
    ok(null, 'Deposit rejected');
}

// ============================================================
// TRAFFIC ALERTS
// ============================================================
if ($action === 'traffic_list') {
    $alerts = $d->fetchAll("SELECT ta.*, u.fullname as reporter_name,
        (SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=ta.id AND vote='confirm') as confirms,
        (SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=ta.id AND vote='deny') as denies
        FROM traffic_alerts ta JOIN users u ON ta.user_id=u.id ORDER BY ta.id DESC LIMIT 50");
    ok($alerts);
}

if ($action === 'traffic_delete' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    $d->query("DELETE FROM traffic_alerts WHERE id=?", [$id]);
    ok(null, 'Alert deleted');
}

// ============================================================
// ANALYTICS
// ============================================================
if ($action === 'analytics') {
    $period = $_GET['period'] ?? '7d';
    $days = $period === '30d' ? 30 : ($period === '24h' ? 1 : 7);

    $data = ['period' => $period];

    try {
        $data['page_views'] = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as views, COUNT(DISTINCT ip_hash) as unique_visitors FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY date");
        $data['top_pages'] = $d->fetchAll("SELECT page, COUNT(*) as views FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY page ORDER BY views DESC LIMIT 15");
        $data['top_referrers'] = $d->fetchAll("SELECT referrer, COUNT(*) as views FROM analytics_views WHERE referrer IS NOT NULL AND referrer != '' AND created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY referrer ORDER BY views DESC LIMIT 10");
        $data['devices'] = $d->fetchAll("SELECT device, COUNT(*) as views FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY device");
        $data['total_views'] = $d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)")['c'];
        $data['unique_visitors'] = $d->fetchOne("SELECT COUNT(DISTINCT ip_hash) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)")['c'];
    } catch(Throwable $e) {
        $data['error'] = 'Analytics table not ready';
    }

    // User growth
    $data['user_growth'] = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as new_users FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY date");

    // Post activity
    $data['post_activity'] = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as posts FROM posts WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY date");

    ok($data);
}

// ============================================================
// REFERRALS
// ============================================================
if ($action === 'referrals') {
    try {
        $total = $d->fetchOne("SELECT COUNT(*) as c FROM referrals")['c'];
        $recent = $d->fetchAll("SELECT r.*, 
            u1.fullname as inviter_name, u2.fullname as invitee_name
            FROM referrals r 
            JOIN users u1 ON r.inviter_id=u1.id 
            JOIN users u2 ON r.invitee_id=u2.id 
            ORDER BY r.created_at DESC LIMIT 20");
        $topInviters = $d->fetchAll("SELECT r.inviter_id, u.fullname, COUNT(*) as count 
            FROM referrals r JOIN users u ON r.inviter_id=u.id 
            GROUP BY r.inviter_id ORDER BY count DESC LIMIT 10");
        ok(['total' => (int)$total, 'recent' => $recent, 'top_inviters' => $topInviters]);
    } catch(Throwable $e) {
        ok(['total' => 0, 'recent' => [], 'top_inviters' => []]);
    }
}

// ============================================================
// SYSTEM
// ============================================================
if ($action === 'system') {
    $dbSize = $d->fetchOne("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()")['size_mb'];
    $tableStats = $d->fetchAll("SELECT table_name, table_rows, ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY data_length DESC");

    ok([
        'php_version' => PHP_VERSION,
        'db_size_mb' => $dbSize,
        'tables' => $tableStats,
        'server_time' => date('Y-m-d H:i:s'),
        'disk_free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB'
    ]);
}

// ============================================================
// PUSH NOTIFICATIONS (broadcast)
// ============================================================
if ($action === 'broadcast' && $method === 'POST') {
    $in = input();
    $title = trim($in['title'] ?? '');
    $body = trim($in['body'] ?? '');
    $url = trim($in['url'] ?? '/');
    if (!$title || !$body) err('Title and body required');

    require_once __DIR__ . '/../includes/push-helper.php';
    $subs = $d->fetchAll("SELECT * FROM push_subscriptions");
    $sent = 0;
    foreach ($subs as $sub) {
        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url, 'category' => 'broadcast']);
        $result = sendPushNotification($sub, $payload);
        if ($result['success']) $sent++;
    }
    ok(['sent' => $sent, 'total' => count($subs)], "Sent to $sent devices");
}

// ============================================================
// COMMENTS MANAGEMENT
// ============================================================
if ($action === 'comments') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');

    $where = [];
    $params = [];
    if ($search) { $where[] = "(c.content LIKE ? OR u.fullname LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $whereStr = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = $d->fetchOne("SELECT COUNT(*) as c FROM comments c JOIN users u ON c.user_id=u.id $whereStr", $params)['c'];
    $items = $d->fetchAll("SELECT c.*, u.fullname as user_name, u.avatar as user_avatar
        FROM comments c JOIN users u ON c.user_id=u.id $whereStr
        ORDER BY c.id DESC LIMIT $limit OFFSET $offset", $params);

    ok(['comments' => $items, 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

if ($action === 'comment_delete' && $method === 'POST') {
    $in = input();
    $id = intval($in['id'] ?? 0);
    $d->query("DELETE FROM comments WHERE id=?", [$id]);
    ok(null, 'Comment deleted');
}

if ($action === 'broadcast' && $method === 'POST') {
    $in = input();
    $title = trim($in['title'] ?? '');
    $body = trim($in['body'] ?? '');
    if (!$title || !$body) err('Cần title và body');
    ok(null, 'Broadcast queued (push not yet implemented)');
}

err('Unknown action: ' . $action, 404);
