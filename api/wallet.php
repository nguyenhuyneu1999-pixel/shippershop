<?php
/**
 * ============================================
 * WALLET API
 * ============================================
 * 
 * Endpoints:
 * GET /api/wallet.php?action=balance - Get wallet balance & stats
 * POST /api/wallet.php?action=deposit - Request deposit (with proof)
 * POST /api/wallet.php?action=withdraw - Request withdrawal
 * GET /api/wallet.php?action=history - Get transaction history
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

// Require authentication for all wallet actions
requireAuth();
$userId = getCurrentUserId();

// ============================================
// GET BALANCE & STATS
// ============================================

if ($action === 'balance') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    // Get wallet
    $wallet = $db->fetchOne("SELECT * FROM wallet WHERE user_id = ?", [$userId]);
    
    if (!$wallet) {
        // Create wallet if doesn't exist
        $db->insert('wallet', [
            'user_id' => $userId,
            'balance' => 0
        ]);
        
        $wallet = ['balance' => 0];
    }
    
    // Get stats
    $stats = [
        'balance' => $wallet['balance'],
        'total_deposit' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as c FROM wallet_transactions 
             WHERE user_id = ? AND type = 'deposit' AND status = 'completed'", [$userId])['c'],
        'total_withdraw' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as c FROM wallet_transactions 
             WHERE user_id = ? AND type = 'withdraw' AND status = 'completed'", [$userId])['c'],
        'total_spent' => $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as c FROM wallet_transactions 
             WHERE user_id = ? AND type = 'purchase' AND status = 'completed'", [$userId])['c'],
        'pending_count' => $db->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE user_id = ? AND `status` = ?", [$userId, 'pending'])['c']
    ];
    
    success('Success', $stats);
}

// ============================================
// DEPOSIT REQUEST
// ============================================

if ($action === 'deposit') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    // Get amount
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    if ($amount < 10000) {
        error('Số tiền nạp tối thiểu là 10,000₫');
    }
    
    if ($amount > 50000000) {
        error('Số tiền nạp tối đa là 50,000,000₫');
    }
    
    // Upload proof of payment
    $proofUrl = null;
    
    if (!empty($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['proof'], 'payment-proofs');
        
        if ($uploadResult['success']) {
            $proofUrl = $uploadResult['url'];
        } else {
            error($uploadResult['message']);
        }
    }
    
    if (!$proofUrl) {
        error('Vui lòng upload ảnh xác nhận chuyển khoản');
    }
    
    try {
        $db->beginTransaction();
        
        // Get current balance
        $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$userId]);
        
        if (!$wallet) {
            // Create wallet
            $db->insert('wallet', [
                'user_id' => $userId,
                'balance' => 0
            ]);
            $currentBalance = 0;
        } else {
            $currentBalance = $wallet['balance'];
        }
        
        // Create transaction record
        $transactionId = $db->insert('wallet_transactions', [
            'user_id' => $userId,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance, // Will be updated when approved
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'description' => "Nạp tiền vào ví",
            'proof_url' => $proofUrl
        ]);
        
        $db->commit();
        
        success('Yêu cầu nạp tiền đã được gửi. Vui lòng chờ xác nhận.', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'status' => 'pending'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        
        if (DEBUG_MODE) {
            error('Nạp tiền thất bại: ' . $e->getMessage(), 500);
        } else {
            error('Nạp tiền thất bại. Vui lòng thử lại', 500);
        }
    }
}

// ============================================
// WITHDRAW REQUEST
// ============================================

if ($action === 'withdraw') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    
    $amount = floatval($input['amount'] ?? 0);
    $bankName = sanitize($input['bank_name'] ?? '');
    $accountNumber = sanitize($input['account_number'] ?? '');
    $accountName = sanitize($input['account_name'] ?? '');
    $note = sanitize($input['note'] ?? '');
    
    // Validation
    if ($amount < 50000) {
        error('Số tiền rút tối thiểu là 50,000₫');
    }
    
    if (empty($bankName) || empty($accountNumber) || empty($accountName)) {
        error('Vui lòng điền đầy đủ thông tin tài khoản');
    }
    
    try {
        $db->beginTransaction();
        
        // Get current balance
        $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$userId]);
        
        if (!$wallet || $wallet['balance'] < $amount) {
            error('Số dư không đủ', 400);
        }
        
        $currentBalance = $wallet['balance'];
        $newBalance = $currentBalance - $amount;
        
        // Update wallet (deduct immediately, will refund if rejected)
        $db->update('wallet',
            ['balance' => $newBalance],
            'user_id = ?',
            [$userId]
        );
        
        // Create transaction record
        $transactionId = $db->insert('wallet_transactions', [
            'user_id' => $userId,
            'type' => 'withdraw',
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $newBalance,
            'payment_method' => 'bank_transfer',
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'status' => 'pending',
            'description' => "Rút tiền từ ví" . ($note ? " - $note" : '')
        ]);
        
        $db->commit();
        
        success('Yêu cầu rút tiền đã được gửi. Tiền sẽ được chuyển trong 1-3 ngày làm việc.', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'new_balance' => $newBalance,
            'status' => 'pending'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        
        if (DEBUG_MODE) {
            error('Rút tiền thất bại: ' . $e->getMessage(), 500);
        } else {
            error('Rút tiền thất bại. Vui lòng thử lại', 500);
        }
    }
}

// ============================================
// ADMIN: APPROVE DEPOSIT
// ============================================

if ($action === 'approve_deposit') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    requireAdmin();
    
    $input = getJsonInput();
    $transactionId = intval($input['transaction_id'] ?? 0);
    
    if ($transactionId <= 0) {
        error('Transaction ID không hợp lệ');
    }
    
    try {
        $db->beginTransaction();
        
        // Get transaction
        $transaction = $db->fetchOne(
            "SELECT * FROM wallet_transactions WHERE id = ? AND type = 'deposit' AND status = 'pending'",
            [$transactionId]
        );
        
        if (!$transaction) {
            error('Giao dịch không tồn tại hoặc đã được xử lý', 404);
        }
        
        // Get wallet
        $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$transaction['user_id']]);
        $currentBalance = $wallet['balance'];
        $newBalance = $currentBalance + $transaction['amount'];
        
        // Update wallet
        $db->update('wallet',
            ['balance' => $newBalance],
            'user_id = ?',
            [$transaction['user_id']]
        );
        
        // Update transaction
        $db->update('wallet_transactions',
            [
                'status' => 'completed',
                'balance_after' => $newBalance,
                'completed_at' => date('Y-m-d H:i:s'),
                'approved_by' => getCurrentUserId()
            ],
            'id = ?',
            [$transactionId]
        );
        
        $db->commit();
        
        success('Đã duyệt nạp tiền thành công', [
            'transaction_id' => $transactionId,
            'amount' => $transaction['amount'],
            'new_balance' => $newBalance
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        error('Duyệt giao dịch thất bại: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ADMIN: REJECT DEPOSIT
// ============================================

if ($action === 'reject_deposit') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    requireAdmin();
    
    $input = getJsonInput();
    $transactionId = intval($input['transaction_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? '');
    
    if ($transactionId <= 0) {
        error('Transaction ID không hợp lệ');
    }
    
    // Get transaction
    $transaction = $db->fetchOne(
        "SELECT * FROM wallet_transactions WHERE id = ? AND type = 'deposit' AND status = 'pending'",
        [$transactionId]
    );
    
    if (!$transaction) {
        error('Giao dịch không tồn tại hoặc đã được xử lý', 404);
    }
    
    // Update transaction
    $db->update('wallet_transactions',
        [
            'status' => 'rejected',
            'completed_at' => date('Y-m-d H:i:s'),
            'approved_by' => getCurrentUserId(),
            'description' => $transaction['description'] . ($reason ? " - Lý do từ chối: $reason" : '')
        ],
        'id = ?',
        [$transactionId]
    );
    
    success('Đã từ chối giao dịch nạp tiền');
}

// ============================================
// ADMIN: APPROVE WITHDRAW
// ============================================

if ($action === 'approve_withdraw') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    requireAdmin();
    
    $input = getJsonInput();
    $transactionId = intval($input['transaction_id'] ?? 0);
    
    if ($transactionId <= 0) {
        error('Transaction ID không hợp lệ');
    }
    
    // Get transaction
    $transaction = $db->fetchOne(
        "SELECT * FROM wallet_transactions WHERE id = ? AND type = 'withdraw' AND status = 'pending'",
        [$transactionId]
    );
    
    if (!$transaction) {
        error('Giao dịch không tồn tại hoặc đã được xử lý', 404);
    }
    
    // Update transaction
    $db->update('wallet_transactions',
        [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'approved_by' => getCurrentUserId()
        ],
        'id = ?',
        [$transactionId]
    );
    
    success('Đã duyệt rút tiền thành công. Vui lòng chuyển tiền cho khách hàng.', [
        'transaction_id' => $transactionId,
        'amount' => $transaction['amount'],
        'bank_name' => $transaction['bank_name'],
        'account_number' => $transaction['account_number'],
        'account_name' => $transaction['account_name']
    ]);
}

// ============================================
// ADMIN: REJECT WITHDRAW (REFUND)
// ============================================

if ($action === 'reject_withdraw') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    requireAdmin();
    
    $input = getJsonInput();
    $transactionId = intval($input['transaction_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? '');
    
    if ($transactionId <= 0) {
        error('Transaction ID không hợp lệ');
    }
    
    try {
        $db->beginTransaction();
        
        // Get transaction
        $transaction = $db->fetchOne(
            "SELECT * FROM wallet_transactions WHERE id = ? AND type = 'withdraw' AND status = 'pending'",
            [$transactionId]
        );
        
        if (!$transaction) {
            error('Giao dịch không tồn tại hoặc đã được xử lý', 404);
        }
        
        // Refund to wallet
        $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$transaction['user_id']]);
        $newBalance = $wallet['balance'] + $transaction['amount'];
        
        $db->update('wallet',
            ['balance' => $newBalance],
            'user_id = ?',
            [$transaction['user_id']]
        );
        
        // Update transaction
        $db->update('wallet_transactions',
            [
                'status' => 'rejected',
                'completed_at' => date('Y-m-d H:i:s'),
                'approved_by' => getCurrentUserId(),
                'description' => $transaction['description'] . ($reason ? " - Lý do từ chối: $reason" : '') . " (Đã hoàn tiền)"
            ],
            'id = ?',
            [$transactionId]
        );
        
        $db->commit();
        
        success('Đã từ chối rút tiền và hoàn tiền vào ví', [
            'refunded_amount' => $transaction['amount'],
            'new_balance' => $newBalance
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        error('Từ chối giao dịch thất bại: ' . $e->getMessage(), 500);
    }
}

// Invalid action
error('Invalid action', 400);
