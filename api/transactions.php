<?php
/**
 * ============================================
 * TRANSACTIONS API
 * ============================================
 * 
 * Endpoints:
 * GET /api/transactions.php - Get transaction history
 * GET /api/transactions.php?id=1 - Get transaction details
 * GET /api/transactions.php?type=deposit - Filter by type
 */

define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Set CORS headers
setCorsHeaders();

// Get database
$db = db();

// Get request method
$method = getRequestMethod();

// Only allow GET requests
if ($method !== 'GET') {
    error('Method not allowed', 405);
}

// Require authentication
requireAuth();
$userId = getCurrentUserId();

// ============================================
// GET SINGLE TRANSACTION
// ============================================

if (isset($_GET['id'])) {
    $transactionId = intval($_GET['id']);
    
    $sql = "SELECT * FROM wallet_transactions 
            WHERE id = ? AND user_id = ?";
    
    $transaction = $db->fetchOne($sql, [$transactionId, $userId]);
    
    if (!$transaction) {
        error('Giao dịch không tồn tại', 404);
    }
    
    success('Success', $transaction);
}

// ============================================
// GET TRANSACTION HISTORY
// ============================================

// Build WHERE clause
$where = ["user_id = ?"];
$params = [$userId];

// Filter by type
if (!empty($_GET['type'])) {
    $type = sanitize($_GET['type']);
    $allowedTypes = ['deposit', 'withdraw', 'purchase', 'refund'];
    
    if (in_array($type, $allowedTypes)) {
        $where[] = "type = ?";
        $params[] = $type;
    }
}

// Filter by status
if (!empty($_GET['status'])) {
    $status = sanitize($_GET['status']);
    $allowedStatuses = ['pending', 'completed', 'rejected'];
    
    if (in_array($status, $allowedStatuses)) {
        $where[] = "status = ?";
        $params[] = $status;
    }
}

// Filter by date range
if (!empty($_GET['from_date'])) {
    $fromDate = sanitize($_GET['from_date']);
    $where[] = "DATE(created_at) >= ?";
    $params[] = $fromDate;
}

if (!empty($_GET['to_date'])) {
    $toDate = sanitize($_GET['to_date']);
    $where[] = "DATE(created_at) <= ?";
    $params[] = $toDate;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 20;

// Count total
$total = $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE $whereClause", $params)['c'];

$pagination = paginate($total, $page, $limit);

// Get transactions
$sql = "SELECT * FROM wallet_transactions 
        WHERE $whereClause
        ORDER BY created_at DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$transactions = $db->fetchAll($sql, $params);

// Response
jsonResponse([
    'success' => true,
    'message' => 'Success',
    'data' => $transactions,
    'pagination' => $pagination,
    'filters' => [
        'type' => $_GET['type'] ?? null,
        'status' => $_GET['status'] ?? null,
        'from_date' => $_GET['from_date'] ?? null,
        'to_date' => $_GET['to_date'] ?? null
    ]
]);
