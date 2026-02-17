<?php
/**
 * ============================================
 * CART API
 * ============================================
 * 
 * Endpoints:
 * GET /api/cart.php - Get cart items
 * POST /api/cart.php - Add to cart
 * PUT /api/cart.php - Update cart item
 * DELETE /api/cart.php - Remove from cart
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

// Get user ID (require authentication)
requireAuth();
$userId = getCurrentUserId();

// ============================================
// GET CART
// ============================================

if ($method === 'GET') {
    $sql = "SELECT 
                c.id as cart_id,
                c.quantity,
                c.added_at,
                p.id as product_id,
                p.name,
                p.slug,
                p.price,
                p.sale_price,
                p.main_image,
                p.stock,
                (p.sale_price * c.quantity) as subtotal
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.added_at DESC";
    
    $cartItems = $db->fetchAll($sql, [$userId]);
    
    // Calculate totals
    $subtotal = 0;
    $totalItems = 0;
    
    foreach ($cartItems as $item) {
        $subtotal += $item['subtotal'];
        $totalItems += $item['quantity'];
    }
    
    $shippingFee = calculateShippingFee($subtotal);
    $total = $subtotal + $shippingFee;
    
    success('Success', [
        'items' => $cartItems,
        'summary' => [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'total_items' => $totalItems,
            'items_count' => count($cartItems)
        ]
    ]);
}

// ============================================
// ADD TO CART
// ============================================

if ($method === 'POST') {
    $input = getJsonInput();
    
    $productId = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);
    
    if ($productId <= 0) {
        error('Product ID không hợp lệ');
    }
    
    if ($quantity <= 0) {
        error('Số lượng không hợp lệ');
    }
    
    // Check if product exists and has stock
    $product = $db->fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$productId]);
    
    if (!$product) {
        error('Sản phẩm không tồn tại');
    }
    
    if ($product['stock'] < $quantity) {
        error('Không đủ hàng trong kho. Còn lại: ' . $product['stock']);
    }
    
    // Check if already in cart
    $existing = $db->fetchOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
    
    if ($existing) {
        // Update quantity
        $newQuantity = $existing['quantity'] + $quantity;
        
        if ($newQuantity > $product['stock']) {
            error('Không đủ hàng trong kho. Còn lại: ' . $product['stock']);
        }
        
        $db->update('cart', 
            ['quantity' => $newQuantity],
            'id = ?',
            [$existing['id']]
        );
        
        success('Đã cập nhật số lượng trong giỏ hàng', [
            'cart_id' => $existing['id'],
            'quantity' => $newQuantity
        ]);
        
    } else {
        // Add new item
        $cartId = $db->insert('cart', [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
        
        success('Đã thêm vào giỏ hàng', [
            'cart_id' => $cartId,
            'quantity' => $quantity
        ]);
    }
}

// ============================================
// UPDATE CART
// ============================================

if ($method === 'PUT') {
    $input = getJsonInput();
    
    $cartId = intval($input['cart_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 0);
    
    if ($cartId <= 0) {
        error('Cart ID không hợp lệ');
    }
    
    if ($quantity < 0) {
        error('Số lượng không hợp lệ');
    }
    
    // Get cart item
    $cartItem = $db->fetchOne("SELECT c.*, p.stock 
                               FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.id = ? AND c.user_id = ?", 
                               [$cartId, $userId]);
    
    if (!$cartItem) {
        error('Cart item không tồn tại');
    }
    
    // If quantity is 0, remove item
    if ($quantity === 0) {
        $db->hardDelete('cart', 'id = ?', [$cartId]);
        success('Đã xóa khỏi giỏ hàng');
    }
    
    // Check stock
    if ($quantity > $cartItem['stock']) {
        error('Không đủ hàng trong kho. Còn lại: ' . $cartItem['stock']);
    }
    
    // Update quantity
    $db->update('cart',
        ['quantity' => $quantity],
        'id = ?',
        [$cartId]
    );
    
    success('Đã cập nhật số lượng', [
        'cart_id' => $cartId,
        'quantity' => $quantity
    ]);
}

// ============================================
// DELETE FROM CART
// ============================================

if ($method === 'DELETE') {
    $cartId = intval($_GET['cart_id'] ?? 0);
    
    if ($cartId <= 0) {
        error('Cart ID không hợp lệ');
    }
    
    // Verify ownership
    $cartItem = $db->fetchOne("SELECT * FROM cart WHERE id = ? AND user_id = ?", [$cartId, $userId]);
    
    if (!$cartItem) {
        error('Cart item không tồn tại');
    }
    
    // Delete
    $db->hardDelete('cart', 'id = ?', [$cartId]);
    
    success('Đã xóa khỏi giỏ hàng');
}

// Method not allowed
error('Method not allowed', 405);
