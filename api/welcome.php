<?php
/**
 * Welcome System - tự động chào user mới
 * Called after registration from auth.php
 */
require_once __DIR__ . '/../includes/db.php';

function welcomeNewUser($userId) {
    $d = db();
    
    // 1. Send welcome notification
    try {
        $d->query("INSERT INTO notifications (user_id, type, message, link, created_at) VALUES (?, 'system', ?, ?, NOW())", [
            $userId,
            '🎉 Chào mừng ae đến ShipperShop! Bắt đầu chia sẻ kinh nghiệm giao hàng với ae shipper khác nhé. Bấm vào đây để check-in nhận XP đầu tiên!',
            '/profile.html'
        ]);
    } catch (Exception $e) {}
    
    // 2. Auto-follow admin account
    try {
        $d->query("INSERT IGNORE INTO follows (follower_id, following_id, created_at) VALUES (?, 2, NOW())", [$userId]);
    } catch (Exception $e) {}
    
    // 3. Award first XP
    try {
        $d->query("INSERT INTO user_xp (user_id, action, xp, detail, created_at) VALUES (?, 'register', 10, 'Đăng ký thành công', NOW())", [$userId]);
        $d->query("INSERT INTO user_streaks (user_id, total_xp, level, current_streak, longest_streak, last_active_date) VALUES (?, 10, 1, 1, 1, CURDATE()) ON DUPLICATE KEY UPDATE total_xp = total_xp + 10", [$userId]);
    } catch (Exception $e) {}
    
    // 4. Create referral code
    try {
        $user = $d->fetchOne("SELECT fullname FROM users WHERE id = ?", [$userId]);
        $name = $user ? strtoupper(preg_replace('/[^a-zA-Z]/', '', substr($user['fullname'], 0, 4))) : 'SHIP';
        if (strlen($name) < 2) $name = 'SHIP';
        $code = $name . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $d->query("INSERT INTO referral_codes (user_id, code, created_at) VALUES (?, ?, NOW())", [$userId, $code]);
        $d->query("UPDATE users SET ref_code = ? WHERE id = ?", [$code, $userId]);
    } catch (Exception $e) {}
    
    return true;
}

// Standalone endpoint for testing
if (isset($_GET['test']) && isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    $uid = intval($_GET['user_id']);
    welcomeNewUser($uid);
    echo json_encode(['success' => true, 'message' => "Welcome sent to user $uid"]);
}
