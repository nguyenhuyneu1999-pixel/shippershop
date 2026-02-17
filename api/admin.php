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
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    // Today's revenue
    $todayRevenue = $db->fetchColumn(
        "SELECT COALESCE(SUM(total), 0) FROM orders 
         WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'",
        []
    ) ?? 0;
    
    // New orders today
    $newOrders = $db->count(
        'orders',
        "DATE(created_at) = CURDATE()",
        []
    );
    
    // New users today
    $newUsers = $db->count(
        'users',
        "DATE(created_at) = CURDATE() AND status = 'active'",
        []
    );
    
    // Pending items (orders + wallet transactions)
    $pendingOrders = $db->count('orders', 'status = ?', ['pending']);
    $pendingWallet = $db->count('transaction_history', 'status = ?', ['pending']);
    $pendingItems = $pendingOrders + $pendingWallet;
    
    success('Success', [
        'today_revenue' => $todayRevenue,
        'new_orders' => $newOrders,
        'new_users' => $newUsers,
        'pending_items' => $pendingItems,
        'pending_orders' => $pendingOrders,
        'pending_wallet' => $pendingWallet
    ]);
}

// ============================================
// ORDERS STATS
// ============================================

if ($action === 'orders_stats') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $totalOrders = $db->count('orders', '1=1', []);
    
    $pendingOrders = $db->count('orders', 'status = ?', ['pending']);
    
    $shippingOrders = $db->count('orders', 'status = ?', ['shipping']);
    
    $totalRevenue = $db->fetchColumn(
        "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'",
        []
    ) ?? 0;
    
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
    
    $pendingCount = $db->count('transaction_history', 'status = ?', ['pending']);
    
    $pendingDepositAmount = $db->fetchColumn(
        "SELECT COALESCE(SUM(amount), 0) FROM transaction_history 
         WHERE type = 'deposit' AND status = 'pending'",
        []
    ) ?? 0;
    
    $pendingWithdrawAmount = $db->fetchColumn(
        "SELECT COALESCE(SUM(amount), 0) FROM transaction_history 
         WHERE type = 'withdraw' AND status = 'pending'",
        []
    ) ?? 0;
    
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
            FROM transaction_history t
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
            FROM transaction_history t
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
            $db->insert('transaction_history', [
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
        'total_users' => $db->count('users', "status = 'active'", []),
        'total_orders' => $db->count('orders', '1=1', []),
        'total_products' => $db->count('products', "status = 'active'", []),
        'total_posts' => $db->count('posts', "status = 'active'", []),
        'total_wallet_balance' => $db->fetchColumn("SELECT COALESCE(SUM(balance), 0) FROM wallet", []) ?? 0,
        'pending_deposits' => $db->count('transaction_history', "type = 'deposit' AND status = 'pending'", []),
        'pending_withdraws' => $db->count('transaction_history', "type = 'withdraw' AND status = 'pending'", []),
        'total_revenue_all_time' => $db->fetchColumn("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'", []) ?? 0
    ];
    
    success('Success', $stats);
}

// Invalid action
error('Invalid action', 400);
