<?php
/**
 * ============================================
 * ADMIN API
 * ============================================
 * 
 * Endpoints:
 * GET /api/admin.php?action=dashboard_stats - Dashboard statistics
 * GET /api/admin.php?action=orders_stats - Orders statistics
 * GET /api/admin.php?action=wallet_stats - Wallet statistics
 * GET /api/admin.php?action=orders - Get all orders
 * GET /api/admin.php?action=recent_orders - Recent orders
 * GET /api/admin.php?action=pending_wallet - Pending wallet requests
 * GET /api/admin.php?action=wallet_transactions - All wallet transactions
 * POST /api/admin.php?action=update_order_status - Update order status
 */

define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set CORS headers
setCorsHeaders();

// Get database
$db = db();

// Get action
$action = $_GET['action'] ?? '';

// Require admin authentication for ALL actions
requireAuth();
requireAdmin();

// ============================================
// DASHBOARD STATS
// ============================================

if ($action === 'dashboard_stats') {
    $filter = $_GET['filter'] ?? 'all'; // all | real
    $seedMin = 3; $seedMax = 102; // seed user IDs range
    
    // User filter conditions
    $userAll = "id > 1";
    $userReal = "(id = 2 OR id > $seedMax)";
    $postAll = "`status` = 'active'";
    $postReal = "`status` = 'active' AND (user_id = 2 OR user_id > $seedMax)";
    
    $uf = ($filter === 'real') ? $userReal : $userAll;
    $pf = ($filter === 'real') ? $postReal : $postAll;
    
    // Users
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf")['c'];
    $realUsers = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE $userReal")['c'];
    $seedUsers = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE id >= $seedMin AND id <= $seedMax")['c'];
    $todayUsers = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf AND DATE(created_at) = CURDATE()")['c'];
    
    // Posts
    $totalPosts = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf")['c'];
    $realPosts = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $postReal")['c'];
    $seedPosts = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id >= $seedMin AND user_id <= $seedMax")['c'];
    $todayPosts = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf AND DATE(created_at) = CURDATE()")['c'];
    
    // Comments & Likes
    $cmtFilter = ($filter === 'real') ? "AND (c.user_id = 2 OR c.user_id > $seedMax)" : "";
    $totalCmts = $db->fetchOne("SELECT COUNT(*) as c FROM comments c WHERE 1=1 $cmtFilter")['c'];
    $todayCmts = $db->fetchOne("SELECT COUNT(*) as c FROM comments c WHERE DATE(c.created_at) = CURDATE() $cmtFilter")['c'];
    $totalLikes = $db->fetchOne("SELECT COUNT(*) as c FROM likes")['c'];
    
    // Groups & Group posts
    $totalGroups = $db->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c'];
    $totalGP = $db->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c'];
    
    // Marketplace
    $totalMk = $db->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings WHERE `status`='active'")['c'];
    
    // Traffic
    $totalTraffic = $db->fetchOne("SELECT COUNT(*) as c FROM traffic_alerts")['c'];
    
    // Messages & Conversations
    $totalMsg = $db->fetchOne("SELECT COUNT(*) as c FROM messages")['c'];
    $totalConv = $db->fetchOne("SELECT COUNT(*) as c FROM conversations")['c'];
    
    // Wallet & Subscriptions
    $totalWallets = $db->fetchOne("SELECT COUNT(*) as c FROM wallets")['c'];
    $totalBalance = $db->fetchOne("SELECT COALESCE(SUM(balance),0) as s FROM wallets")['s'];
    $activeSubs = $db->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active'")['c'];
    $pendingDeposits = $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE `status`='pending'")['c'];
    
    // Push subscriptions
    $pushSubs = $db->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
    
    // Recent activity (last 7 days)
    $weekPosts = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $pf AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['c'];
    $weekUsers = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE $uf AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['c'];
    
    // Posts by day (last 7 days)
    $postsByDay = $db->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM posts WHERE $pf AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
    
    // Top posters
    $topPosters = $db->fetchAll("SELECT u.id, u.fullname, u.avatar, COUNT(p.id) as post_count FROM users u JOIN posts p ON u.id = p.user_id WHERE p.`status`='active' AND u.id > 1 " . ($filter === 'real' ? "AND (u.id = 2 OR u.id > $seedMax)" : "") . " GROUP BY u.id ORDER BY post_count DESC LIMIT 5");
    
    success('Success', [
        'filter' => $filter,
        'users' => ['total' => (int)$totalUsers, 'real' => (int)$realUsers, 'seed' => (int)$seedUsers, 'today' => (int)$todayUsers],
        'posts' => ['total' => (int)$totalPosts, 'real' => (int)$realPosts, 'seed' => (int)$seedPosts, 'today' => (int)$todayPosts, 'week' => (int)$weekPosts],
        'comments' => ['total' => (int)$totalCmts, 'today' => (int)$todayCmts],
        'likes' => (int)$totalLikes,
        'groups' => ['total' => (int)$totalGroups, 'posts' => (int)$totalGP],
        'marketplace' => (int)$totalMk,
        'traffic' => (int)$totalTraffic,
        'messages' => ['total' => (int)$totalMsg, 'conversations' => (int)$totalConv],
        'wallet' => ['total' => (int)$totalWallets, 'balance' => $totalBalance, 'active_subs' => (int)$activeSubs, 'pending_deposits' => (int)$pendingDeposits],
        'push_subs' => (int)$pushSubs,
        'week_users' => (int)$weekUsers,
        'posts_by_day' => $postsByDay,
        'top_posters' => $topPosters
    ]);
}

// ============================================
// USERS LIST (with filter: all / real / seed)
// ============================================

if ($action === 'users') {
    $filter = $_GET['filter'] ?? 'all'; // all | real | seed
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $seedMin = 3; $seedMax = 102;
    
    $where = ["id > 1"];
    $params = [];
    
    if ($filter === 'real') {
        $where[] = "(id = 2 OR id > $seedMax)";
    } elseif ($filter === 'seed') {
        $where[] = "id >= $seedMin AND id <= $seedMax";
    }
    
    if ($search) {
        $where[] = "(fullname LIKE ? OR username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    
    $whereStr = implode(' AND ', $where);
    
    $total = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE $whereStr", $params)['c'];
    
    $users = $db->fetchAll(
        "SELECT u.id, u.username, u.fullname, u.email, u.avatar, u.role, u.shipping_company, u.created_at,
                (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND `status` = 'active') as post_count,
                (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count,
                (SELECT COUNT(*) FROM likes WHERE user_id = u.id) as like_count
         FROM users u WHERE $whereStr
         ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset", $params
    );
    
    // Mark seed users
    foreach ($users as &$u) {
        $u['is_seed'] = ($u['id'] >= $seedMin && $u['id'] <= $seedMax) ? 1 : 0;
    }
    
    $realCount = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE (id = 2 OR id > $seedMax)")['c'];
    $seedCount = $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE id >= $seedMin AND id <= $seedMax")['c'];
    
    success('Success', [
        'users' => $users,
        'total' => (int)$total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'counts' => ['all' => (int)$realCount + (int)$seedCount, 'real' => (int)$realCount, 'seed' => (int)$seedCount]
    ]);
}

// ============================================
// POSTS LIST (with filter: all / real / seed)
// ============================================

if ($action === 'posts') {
    $filter = $_GET['filter'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $seedMin = 3; $seedMax = 102;
    
    $where = ["`status` = 'active'"];
    $params = [];
    
    if ($filter === 'real') {
        $where[] = "(user_id = 2 OR user_id > $seedMax)";
    } elseif ($filter === 'seed') {
        $where[] = "user_id >= $seedMin AND user_id <= $seedMax";
    }
    
    if ($search) {
        $where[] = "content LIKE ?";
        $params[] = "%$search%";
    }
    
    $whereStr = implode(' AND ', $where);
    
    $total = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE $whereStr", $params)['c'];
    
    $posts = $db->fetchAll(
        "SELECT p.id, p.content, p.images, p.likes_count, p.comments_count, p.shares_count, p.type, p.province, p.district, p.created_at,
                u.id as user_id, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company,
                CASE WHEN u.id >= $seedMin AND u.id <= $seedMax THEN 1 ELSE 0 END as is_seed
         FROM posts p JOIN users u ON p.user_id = u.id
         WHERE p.`status` = 'active'" . ($filter === 'real' ? " AND (p.user_id = 2 OR p.user_id > $seedMax)" : ($filter === 'seed' ? " AND p.user_id >= $seedMin AND p.user_id <= $seedMax" : "")) . ($search ? " AND p.content LIKE ?" : "") . "
         ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset", $params
    );
    
    $realCount = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND (user_id = 2 OR user_id > $seedMax)")['c'];
    $seedCount = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id >= $seedMin AND user_id <= $seedMax")['c'];
    
    success('Success', [
        'posts' => $posts,
        'total' => (int)$total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'counts' => ['all' => (int)$realCount + (int)$seedCount, 'real' => (int)$realCount, 'seed' => (int)$seedCount]
    ]);
}

// ============================================
// ORDERS STATS
// ============================================

if ($action === 'orders_stats') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $totalOrders = $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE 1=1", [])['c'];
    
    $pendingOrders = $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = ?", ['pending'])['c'];
    
    $shippingOrders = $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = ?", ['shipping'])['c'];
    
    $totalRevenue = $db->fetchOne("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE `status` != 'cancelled'", [])['val'];
    
    success('Success', [
        'total_orders' => $totalOrders,
        'pending_orders' => $pendingOrders,
        'shipping_orders' => $shippingOrders,
        'total_revenue' => $totalRevenue
    ]);
}

// ============================================
// WALLET STATS
// ============================================

if ($action === 'wallet_stats') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $pendingCount = $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE status = ?", ['pending'])['c'];
    
    $pendingDepositAmount = $db->fetchOne("SELECT COALESCE(SUM(amount),0) as val FROM wallet_transactions WHERE type='deposit' AND `status`='pending'", [])['val'];
    
    $pendingWithdrawAmount = $db->fetchOne("SELECT COALESCE(SUM(amount),0) as val FROM wallet_transactions WHERE type='withdraw' AND `status`='pending'", [])['val'];
    
    success('Success', [
        'pending_count' => $pendingCount,
        'pending_deposit_amount' => $pendingDepositAmount,
        'pending_withdraw_amount' => $pendingWithdrawAmount
    ]);
}

// ============================================
// GET ORDERS
// ============================================

if ($action === 'orders' || $action === 'recent_orders') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $where = ['1=1'];
    $params = [];
    
    // Filter by status
    if (!empty($_GET['status'])) {
        $status = sanitize($_GET['status']);
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Limit
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 20;
    
    // Get orders with item count
    $sql = "SELECT 
                o.*,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
            FROM orders o
            WHERE $whereClause
            ORDER BY o.created_at DESC
            LIMIT $limit";
    
    $orders = $db->fetchAll($sql, $params);
    
    success('Success', $orders);
}

// ============================================
// PENDING WALLET REQUESTS
// ============================================

if ($action === 'pending_wallet') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 20;
    
    $sql = "SELECT 
                t.*,
                u.fullname as user_name,
                u.email as user_email
            FROM wallet_transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.status = 'pending'
            ORDER BY t.created_at DESC
            LIMIT $limit";
    
    $transactions = $db->fetchAll($sql);
    
    success('Success', $transactions);
}

// ============================================
// WALLET TRANSACTIONS
// ============================================

if ($action === 'wallet_transactions') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $where = ['1=1'];
    $params = [];
    
    // Filter by type
    if (!empty($_GET['type'])) {
        $type = sanitize($_GET['type']);
        $where[] = "t.type = ?";
        $params[] = $type;
    }
    
    // Filter by status
    if (!empty($_GET['status'])) {
        $status = sanitize($_GET['status']);
        $where[] = "t.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
    
    $sql = "SELECT 
                t.*,
                u.fullname as user_name,
                u.email as user_email
            FROM wallet_transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE $whereClause
            ORDER BY t.created_at DESC
            LIMIT $limit";
    
    $transactions = $db->fetchAll($sql, $params);
    
    success('Success', $transactions);
}

// ============================================
// UPDATE ORDER STATUS
// ============================================

if ($action === 'update_order_status') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    
    $orderId = intval($input['order_id'] ?? 0);
    $newStatus = sanitize($input['status'] ?? '');
    
    if ($orderId <= 0) {
        error('Order ID không hợp lệ');
    }
    
    $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled'];
    
    if (!in_array($newStatus, $allowedStatuses)) {
        error('Trạng thái không hợp lệ');
    }
    
    // Get order
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    
    if (!$order) {
        error('Đơn hàng không tồn tại', 404);
    }
    
    // Update status
    $db->update('orders',
        ['status' => $newStatus],
        'id = ?',
        [$orderId]
    );
    
    // If order is cancelled and payment was wallet, refund
    if ($newStatus === 'cancelled' && $order['payment_method'] === 'wallet' && $order['payment_status'] === 'paid') {
        try {
            $db->beginTransaction();
            
            // Get wallet
            $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$order['user_id']]);
            $newBalance = $wallet['balance'] + $order['total'];
            
            // Refund to wallet
            $db->update('wallet',
                ['balance' => $newBalance],
                'user_id = ?',
                [$order['user_id']]
            );
            
            // Create refund transaction
            $db->insert('wallet_transactions', [
                'user_id' => $order['user_id'],
                'type' => 'refund',
                'amount' => $order['total'],
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'description' => "Hoàn tiền đơn hàng #{$order['order_number']}"
            ]);
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            error('Lỗi hoàn tiền: ' . $e->getMessage(), 500);
        }
    }
    
    success('Cập nhật trạng thái thành công', [
        'order_id' => $orderId,
        'new_status' => $newStatus
    ]);
}

// ============================================
// GET ALL USERS (ADMIN)
// ============================================

if ($action === 'users') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
    
    $sql = "SELECT 
                u.id,
                u.fullname,
                u.email,
                u.phone,
                u.role,
                u.status,
                u.created_at,
                w.balance as wallet_balance,
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
                (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
            FROM users u
            LEFT JOIN wallet w ON u.id = w.user_id
            ORDER BY u.created_at DESC
            LIMIT $limit";
    
    $users = $db->fetchAll($sql);
    
    success('Success', $users);
}

// ============================================
// GET ALL PRODUCTS (ADMIN)
// ============================================

if ($action === 'products') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
    
    $sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT $limit";
    
    $products = $db->fetchAll($sql);
    
    success('Success', $products);
}

// ============================================
// SALES REPORT
// ============================================

if ($action === 'sales_report') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $fromDate = isset($_GET['from_date']) ? sanitize($_GET['from_date']) : date('Y-m-01');
    $toDate = isset($_GET['to_date']) ? sanitize($_GET['to_date']) : date('Y-m-d');
    
    // Daily sales
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                COALESCE(SUM(total), 0) as total_revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND status != 'cancelled'
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC";
    
    $dailySales = $db->fetchAll($sql, [$fromDate, $toDate]);
    
    // Top products
    $sql = "SELECT 
                p.name,
                SUM(oi.quantity) as total_sold,
                COALESCE(SUM(oi.subtotal), 0) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status != 'cancelled'
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT 10";
    
    $topProducts = $db->fetchAll($sql, [$fromDate, $toDate]);
    
    // Summary
    $totalRevenue = array_sum(array_column($dailySales, 'total_revenue'));
    $totalOrders = array_sum(array_column($dailySales, 'orders_count'));
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
    
    success('Success', [
        'daily_sales' => $dailySales,
        'top_products' => $topProducts,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'avg_order_value' => $avgOrderValue,
            'from_date' => $fromDate,
            'to_date' => $toDate
        ]
    ]);
}

// ============================================
// UPDATE USER STATUS
// ============================================

if ($action === 'update_user_status') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    
    $userId = intval($input['user_id'] ?? 0);
    $newStatus = sanitize($input['status'] ?? '');
    
    if ($userId <= 0) {
        error('User ID không hợp lệ');
    }
    
    if (!in_array($newStatus, ['active', 'inactive', 'banned'])) {
        error('Trạng thái không hợp lệ');
    }
    
    // Cannot change own status
    if ($userId === getCurrentUserId()) {
        error('Không thể thay đổi trạng thái của chính mình', 403);
    }
    
    $db->update('users',
        ['status' => $newStatus],
        'id = ?',
        [$userId]
    );
    
    success('Cập nhật trạng thái user thành công');
}

// ============================================
// DELETE POST (ADMIN)
// ============================================

if ($action === 'delete_post') {
    if (getRequestMethod() !== 'DELETE') {
        error('Method not allowed', 405);
    }
    
    $postId = intval($_GET['post_id'] ?? 0);
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Soft delete
    $db->update('posts',
        ['status' => 'deleted'],
        'id = ?',
        [$postId]
    );
    
    success('Đã xóa bài viết');
}

// ============================================
// GET SYSTEM STATS
// ============================================

if ($action === 'system_stats') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $stats = [
        'total_users' => $db->fetchOne("SELECT COUNT(*) as c FROM users WHERE status = 'active'", [])['c'],
        'total_orders' => $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE 1=1", [])['c'],
        'total_products' => $db->fetchOne("SELECT COUNT(*) as c FROM products WHERE status = 'active'", [])['c'],
        'total_posts' => $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE status = 'active'", [])['c'],
        'total_wallet_balance' => $db->fetchOne("SELECT COALESCE(SUM(balance),0) as val FROM wallets", [])['val'],
        'pending_deposits' => $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE type = 'deposit' AND status = 'pending'", [])['c'],
        'pending_withdraws' => $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE type = 'withdraw' AND status = 'pending'", [])['c'],
        'total_revenue_all_time' => $db->fetchOne("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE `status` != 'cancelled'", [])['val']
    ];
    
    success('Success', $stats);
}

// Invalid action
error('Invalid action', 400);
