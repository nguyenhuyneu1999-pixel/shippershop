<?php
// Referral System API
// - Generate invite link per user
// - Track signups from ref link
// - Reward both inviter + invitee
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';

header('Content-Type: application/json; charset=utf-8');
$d = db();
$action = $_GET['action'] ?? '';

// Setup tables (run once)
if ($action === 'setup') {
    try {
        $d->query("CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inviter_id INT NOT NULL,
            invitee_id INT NOT NULL,
            ref_code VARCHAR(20) NOT NULL,
            reward_given TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(inviter_id), INDEX(invitee_id), INDEX(ref_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Add ref_code column to users if missing
        try { $d->query("ALTER TABLE users ADD COLUMN ref_code VARCHAR(20) DEFAULT NULL AFTER `status`"); } catch(Throwable $e) {}
        try { $d->query("ALTER TABLE users ADD COLUMN referred_by INT DEFAULT NULL AFTER ref_code"); } catch(Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Tables ready']);
    } catch(Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get my referral info (auth required)
if ($action === 'info') {
    $uid = getOptionalAuthUserId();
    if (!$uid) { echo json_encode(['success' => false, 'message' => 'Login required']); exit; }
    
    $user = $d->fetchOne("SELECT ref_code FROM users WHERE id = ?", [$uid]);
    $refCode = $user['ref_code'] ?? null;
    
    // Generate ref_code if not exists
    if (!$refCode) {
        $refCode = 'SS' . strtoupper(substr(md5($uid . time()), 0, 6));
        $d->query("UPDATE users SET ref_code = ? WHERE id = ?", [$refCode, $uid]);
    }
    
    // Count successful referrals
    $count = $d->fetchOne("SELECT COUNT(*) as c FROM referrals WHERE inviter_id = ?", [$uid]);
    $rewarded = $d->fetchOne("SELECT COUNT(*) as c FROM referrals WHERE inviter_id = ? AND reward_given = 1", [$uid]);
    $recent = $d->fetchAll("SELECT r.*, u.fullname, u.avatar FROM referrals r JOIN users u ON r.invitee_id = u.id WHERE r.inviter_id = ? ORDER BY r.created_at DESC LIMIT 20", [$uid]);
    
    $shareUrl = 'https://shippershop.vn/share.php?type=invite&ref=' . $refCode;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'ref_code' => $refCode,
            'share_url' => $shareUrl,
            'total_invited' => intval($count['c'] ?? 0),
            'total_rewarded' => intval($rewarded['c'] ?? 0),
            'recent' => $recent
        ]
    ]);
    exit;
}

// Record referral (called during registration)
if ($action === 'record') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $refCode = trim($input['ref_code'] ?? '');
    $inviteeId = intval($input['invitee_id'] ?? 0);
    
    if (!$refCode || !$inviteeId) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }
    
    // Find inviter
    $inviter = $d->fetchOne("SELECT id FROM users WHERE ref_code = ?", [$refCode]);
    if (!$inviter) {
        echo json_encode(['success' => false, 'message' => 'Invalid ref code']);
        exit;
    }
    
    $inviterId = intval($inviter['id']);
    if ($inviterId === $inviteeId) {
        echo json_encode(['success' => false, 'message' => 'Cannot refer yourself']);
        exit;
    }
    
    // Check duplicate
    $exists = $d->fetchOne("SELECT id FROM referrals WHERE invitee_id = ?", [$inviteeId]);
    if ($exists) {
        echo json_encode(['success' => false, 'message' => 'Already referred']);
        exit;
    }
    
    // Record referral
    $d->query("INSERT INTO referrals (inviter_id, invitee_id, ref_code) VALUES (?, ?, ?)", [$inviterId, $inviteeId, $refCode]);
    $d->query("UPDATE users SET referred_by = ? WHERE id = ?", [$inviterId, $inviteeId]);
    
    // Auto-follow each other
    try {
        $d->query("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)", [$inviteeId, $inviterId]);
        $d->query("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)", [$inviterId, $inviteeId]);
    } catch(Throwable $e) {}
    
    // Push notification to inviter
    try {
        require_once __DIR__ . '/../includes/push-helper.php';
        $newUser = $d->fetchOne("SELECT fullname FROM users WHERE id = ?", [$inviteeId]);
        $name = $newUser ? $newUser['fullname'] : 'Ai đó';
        notifyUser($inviterId, $name . ' đã tham gia từ lời mời của bạn!', 'Bạn bè mới trên ShipperShop', 'social', '/user.html?id=' . $inviteeId);
    } catch(Throwable $e) {}
    
    echo json_encode(['success' => true, 'message' => 'Referral recorded']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
