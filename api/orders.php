<?php
/**
 * ============================================
 * ORDERS API
 * ============================================
 * 
 * Endpoints:
 * GET /api/orders.php - Get user orders
 * GET /api/orders.php?id=1 - Get single order
 * POST /api/orders.php - Create new order
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

// Require authentication
requireAuth();
$userId = getCurrentUserId();

// ============================================
// GET ORDERS
// ============================================

if ($method === 'GET') {
    
    // Get single order
    if (isset($_GET['id'])) {
        $orderId = intval($_GET['id']);
        
        // Get order
        $order = $db->fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$orderId, $userId]);
        
        if (!$order) {
            error('Đơn hàng không tồn tại', 404);
        }
        
        // Get order items
        $items = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        
        $order['items'] = $items;
        
        success('Success', $order);
    }
    
    // Get all orders
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 10;
    
    // Count total
    $total = $db->count('orders', 'user_id = ?', [$userId]);
    $pagination = paginate($total, $page, $limit);
    
    // Get orders
    $sql = "SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
    
    $orders = $db->fetchAll($sql, [$userId]);
    
    success('Success', [
        'orders' => $orders,
        'pagination' => $pagination
    ]);
}

// ============================================
// CREATE ORDER
// ============================================

if ($method === 'POST') {
    $input = getJsonInput();
    
    // Validate required fields
    $required = ['shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city', 'payment_method'];
    $errors = validateRequired($required, $input);
    
    if (!empty($errors)) {
        error('Vui lòng điền đầy đủ thông tin', 400, $errors);
    }
    
    // Validate phone
    if (!validatePhone($input['shipping_phone'])) {
        error('Số điện thoại không hợp lệ');
    }
    
    // Validate payment method
    $allowedPayments = [PAYMENT_COD, PAYMENT_BANK, PAYMENT_WALLET];
    if (!in_array($input['payment_method'], $allowedPayments)) {
        error('Phương thức thanh toán không hợp lệ');
    }
    
    // Get cart items
    $cartItems = $db->fetchAll(
        "SELECT c.*, p.name, p.sale_price, p.main_image, p.stock 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.user_id = ? AND p.status = 'active'",
        [$userId]
    );
    
    if (empty($cartItems)) {
        error('Giỏ hàng trống');
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['sale_price'] * $item['quantity'];
        
        // Check stock
        if ($item['quantity'] > $item['stock']) {
            error("Sản phẩm '{$item['name']}' không đủ hàng trong kho");
        }
    }
    
    $shippingFee = calculateShippingFee($subtotal);
    $discount = 0; // TODO: Apply voucher
    $total = $subtotal + $shippingFee - $discount;
    
    // Check wallet balance if payment method is wallet
    if ($input['payment_method'] === PAYMENT_WALLET) {
        $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$userId]);
        
        if (!$wallet || $wallet['balance'] < $total) {
            error('Số dư ví không đủ');
        }
    }
    
    // Start transaction
    try {
        $db->beginTransaction();
        
        // Generate order number
        $orderNumber = generateOrderNumber();
        
        // Create order
        $orderId = $db->insert('orders', [
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => sanitize($input['payment_method']),
            'payment_status' => $input['payment_method'] === PAYMENT_COD ? 'pending' : 'pending',
            'shipping_name' => sanitize($input['shipping_name']),
            'shipping_phone' => sanitize($input['shipping_phone']),
            'shipping_address' => sanitize($input['shipping_address']),
            'shipping_city' => sanitize($input['shipping_city']),
            'shipping_country' => sanitize($input['shipping_country'] ?? 'Vietnam'),
            'customer_note' => sanitize($input['customer_note'] ?? ''),
            'status' => 'pending'
        ]);
        
        // Create order items and update stock
        foreach ($cartItems as $item) {
            // Insert order item
            $db->insert('order_items', [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'product_image' => $item['main_image'],
                'price' => $item['sale_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['sale_price'] * $item['quantity']
            ]);
            
            // Update product stock and sales count
            $db->query(
                "UPDATE products 
                 SET stock = stock - ?, 
                     sales_count = sales_count + ? 
                 WHERE id = ?",
                [$item['quantity'], $item['quantity'], $item['product_id']]
            );
        }
        
        // If wallet payment, deduct from wallet
        if ($input['payment_method'] === PAYMENT_WALLET) {
            // Get current balance
            $wallet = $db->fetchOne("SELECT balance FROM wallet WHERE user_id = ?", [$userId]);
            $newBalance = $wallet['balance'] - $total;
            
            // Update wallet
            $db->update('wallet',
                ['balance' => $newBalance],
                'user_id = ?',
                [$userId]
            );
            
            // Record transaction
            $db->insert('transaction_history', [
                'user_id' => $userId,
                'type' => 'purchase',
                'amount' => $total,
                'balance_before' => $wallet['balance'],
                'balance_after' => $newBalance,
                'order_id' => $orderId,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'description' => "Thanh toán đơn hàng #$orderNumber",
                'completed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update order payment status
            $db->update('orders',
                ['payment_status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$orderId]
            );
        }
        
        // Clear cart
        $db->hardDelete('cart', 'user_id = ?', [$userId]);
        
        // Commit transaction
        $db->commit();
        
        // TODO: Send confirmation email
        
        success('Đặt hàng thành công!', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'payment_method' => $input['payment_method']
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        
        if (DEBUG_MODE) {
            error('Đặt hàng thất bại: ' . $e->getMessage(), 500);
        } else {
            error('Đặt hàng thất bại. Vui lòng thử lại', 500);
        }
    }
}

// Method not allowed
error('Method not allowed', 405);
