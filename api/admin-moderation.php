<?php
/**
 * ShipperShop Admin Moderation API
 * Manage reports, ban users, review content
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Admin auth
function adminAuth() {
    $headers = getallheaders();
    $h = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $data = verifyJWT($m[1]);
        if ($data && isset($data['user_id'])) {
            $user = db()->fetchOne("SELECT id, role FROM users WHERE id = ?", [intval($data['user_id'])]);
            if ($user && ($user['role'] === 'admin' || intval($user['id']) === 2)) {
                return intval($user['id']);
            }
        }
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin only']);
    exit;
}

$d = db();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// === GET: List reports ===
if ($method === 'GET' && $action === 'reports') {
    $uid = adminAuth();
    $status = $_GET['status'] ?? 'pending';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $total = intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status` = ?", [$status])['c']);
    $reports = $d->fetchAll(
        "SELECT r.*, p.content as post_content, p.images as post_images, p.`status` as post_status,
                u.fullname as reporter_name, u.avatar as reporter_avatar,
                pu.fullname as post_author_name, pu.avatar as post_author_avatar
         FROM post_reports r
         JOIN users u ON r.user_id = u.id
         JOIN posts p ON r.post_id = p.id
         JOIN users pu ON p.user_id = pu.id
         WHERE r.`status` = ?
         ORDER BY r.created_at DESC LIMIT $limit OFFSET $offset", [$status]
    );
    
    echo json_encode(['success' => true, 'data' => ['reports' => $reports ?: [], 'total' => $total, 'page' => $page]]);
    exit;
}

// === GET: Dashboard stats ===
if ($method === 'GET' && $action === 'stats') {
    $uid = adminAuth();
    api_try_cache('admin_stats', 60);
    
    $stats = [
        'pending_reports' => intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status` = 'pending'")['c']),
        'total_users' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users")['c']),
        'total_posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status` = 'active'")['c']),
        'today_posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status` = 'active' AND DATE(created_at) = CURDATE()")['c']),
        'today_users' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE DATE(created_at) = CURDATE()")['c']),
        'active_subscriptions' => intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status` = 'active' AND expires_at > NOW()")['c']),
        'pending_deposits' => intval($d->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE `status` = 'pending' AND type = 'deposit'")['c']),
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
    exit;
}

// === POST: Review report ===
if ($method === 'POST' && $action === 'review_report') {
    $uid = adminAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    $reportId = intval($input['report_id'] ?? 0);
    $decision = $input['decision'] ?? ''; // 'dismiss' or 'action'
    $note = trim($input['note'] ?? '');
    
    if (!$reportId || !in_array($decision, ['dismiss', 'action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid']);
        exit;
    }
    
    $report = $d->fetchOne("SELECT * FROM post_reports WHERE id = ?", [$reportId]);
    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }
    
    $newStatus = $decision === 'dismiss' ? 'dismissed' : 'actioned';
    $d->query("UPDATE post_reports SET `status` = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?", 
        [$newStatus, $note, $uid, $reportId]);
    
    // If actioned: hide the post
    if ($decision === 'action') {
        $d->query("UPDATE posts SET `status` = 'hidden' WHERE id = ?", [intval($report['post_id'])]);
        api_cache_flush('feed_');
        
        // Notify post author
        try {
            require_once __DIR__ . '/../includes/async-notify.php';
            $post = $d->fetchOne("SELECT user_id FROM posts WHERE id = ?", [intval($report['post_id'])]);
            if ($post) asyncNotify(intval($post['user_id']), 'Bài viết bị ẩn', 'Bài viết vi phạm quy định cộng đồng', 'moderation', '/profile.html');
        } catch (Throwable $e) {}
    }
    
    echo json_encode(['success' => true, 'message' => $decision === 'dismiss' ? 'Đã bỏ qua' : 'Đã xử lý']);
    exit;
}

// === POST: Ban/Unban user ===
if ($method === 'POST' && $action === 'ban_user') {
    $uid = adminAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    $targetId = intval($input['user_id'] ?? 0);
    $ban = intval($input['ban'] ?? 1);
    $reason = trim($input['reason'] ?? '');
    
    if (!$targetId || $targetId === 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid']);
        exit;
    }
    
    $newStatus = $ban ? 'banned' : 'active';
    $d->query("UPDATE users SET `status` = ? WHERE id = ?", [$newStatus, $targetId]);
    
    // Log
    $d->query("INSERT INTO audit_log (user_id, action, details, ip, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$uid, $ban ? 'ban_user' : 'unban_user', "target=$targetId reason=$reason", $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode(['success' => true, 'message' => $ban ? 'Đã cấm' : 'Đã mở cấm']);
    exit;
}


// === POST: Approve/reject deposit ===
if ($method === 'POST' && $action === 'approve_deposit') {
    $uid = adminAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    $txnId = intval($input['transaction_id'] ?? 0);
    $approve = intval($input['approve'] ?? 0);
    
    if (!$txnId) { echo json_encode(['success' => false, 'message' => 'Invalid']); exit; }
    
    $txn = $d->fetchOne("SELECT * FROM wallet_transactions WHERE id = ? AND type = 'deposit' AND `status` = 'pending'", [$txnId]);
    if (!$txn) { echo json_encode(['success' => false, 'message' => 'Transaction not found']); exit; }
    
    $targetUid = intval($txn['user_id']);
    $amount = floatval($txn['amount']);
    
    if ($approve) {
        $pdo = $d->getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$targetUid]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
            $before = floatval($wallet['balance'] ?? 0);
            $after = $before + $amount;
            
            $pdo->prepare("UPDATE wallets SET balance = ?, updated_at = NOW() WHERE user_id = ?")->execute([$after, $targetUid]);
            $pdo->prepare("UPDATE wallet_transactions SET `status` = 'completed', balance_before = ?, balance_after = ? WHERE id = ?")->execute([$before, $after, $txnId]);
            $pdo->commit();
            
            // Notify user
            try {
                require_once __DIR__ . '/../includes/async-notify.php';
                asyncNotify($targetUid, 'Nạp tiền thành công', number_format($amount) . 'đ đã được duyệt. Số dư: ' . number_format($after) . 'đ', 'wallet', '/wallet.html');
            } catch (Throwable $e) {}
            
            echo json_encode(['success' => true, 'message' => 'Đã duyệt ' . number_format($amount) . 'đ']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        $d->query("UPDATE wallet_transactions SET `status` = 'rejected' WHERE id = ?", [$txnId]);
        try {
            require_once __DIR__ . '/../includes/async-notify.php';
            asyncNotify($targetUid, 'Yêu cầu nạp tiền bị từ chối', 'Liên hệ admin để biết thêm.', 'wallet', '/wallet.html');
        } catch (Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Đã từ chối']);
    }
    exit;
}

// === GET: Pending deposits ===
if ($method === 'GET' && $action === 'pending_deposits') {
    $uid = adminAuth();
    $deposits = $d->fetchAll(
        "SELECT wt.*, u.fullname, u.avatar FROM wallet_transactions wt JOIN users u ON wt.user_id = u.id WHERE wt.type = 'deposit' AND wt.`status` = 'pending' ORDER BY wt.created_at DESC LIMIT 50", []);
    echo json_encode(['success' => true, 'data' => $deposits ?: []]);
    exit;
}



// === GET: User list ===
if ($method === 'GET' && $action === 'users') {
    $uid = adminAuth();
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['q'] ?? '');
    $status = $_GET['status'] ?? '';
    
    $where = ['1=1'];
    $params = [];
    if ($search) {
        $where[] = "(u.fullname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    if ($status) { $where[] = "u.`status` = ?"; $params[] = $status; }
    
    $whereStr = implode(' AND ', $where);
    $total = intval($d->fetchOne("SELECT COUNT(*) as c FROM users u WHERE $whereStr", $params)['c']);
    $users = $d->fetchAll(
        "SELECT u.id, u.fullname, u.username, u.email, u.avatar, u.shipping_company, u.`status`, u.created_at, u.is_online, u.last_active,
                (SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active') as post_count,
                (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as follower_count
         FROM users u WHERE $whereStr ORDER BY u.id DESC LIMIT $limit OFFSET $offset", $params
    );
    
    echo json_encode(['success' => true, 'data' => ['users' => $users ?: [], 'total' => $total, 'page' => $page]]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
