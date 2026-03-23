<?php
/**
 * Check-in hàng ngày → +1 đơn giao thành công
 * POST ?action=checkin
 * GET ?action=status
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function ciAuth() {
    if (isset($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $data = verifyJWT($m[1]);
        if ($data && isset($data['user_id'])) return intval($data['user_id']);
    }
    return 0;
}

$d = db();
$action = $_GET['action'] ?? '';

if ($action === 'status') {
    $uid = ciAuth();
    if (!$uid) { success('OK', ['checked_in' => false, 'streak' => 0]); }
    
    $streak = $d->fetchOne("SELECT current_streak, last_active_date FROM user_streaks WHERE user_id = ?", [$uid]);
    $checkedToday = false;
    $currentStreak = 0;
    
    if ($streak) {
        $checkedToday = ($streak['last_active_date'] === date('Y-m-d'));
        $currentStreak = intval($streak['current_streak']);
        if (!$checkedToday && $streak['last_active_date'] !== date('Y-m-d', strtotime('-1 day'))) {
            $currentStreak = 0;
        }
    }
    
    success('OK', [
        'checked_in' => $checkedToday,
        'streak' => $currentStreak,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'checkin') {
    $uid = ciAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    $streak = $d->fetchOne("SELECT id, current_streak, last_active_date FROM user_streaks WHERE user_id = ?", [$uid]);
    
    if ($streak && $streak['last_active_date'] === date('Y-m-d')) {
        error('Hôm nay bạn đã điểm danh rồi!');
    }
    
    $newStreak = 1;
    if ($streak) {
        if ($streak['last_active_date'] === date('Y-m-d', strtotime('-1 day'))) {
            $newStreak = intval($streak['current_streak']) + 1;
        }
        $d->query("UPDATE user_streaks SET current_streak = ?, last_active_date = CURDATE(), longest_streak = GREATEST(longest_streak, ?), updated_at = NOW() WHERE user_id = ?",
            [$newStreak, $newStreak, $uid]);
    } else {
        $d->query("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_active_date, updated_at) VALUES (?, 1, 1, CURDATE(), NOW())", [$uid]);
    }
    
    // +1 đơn giao thành công: tự like 1 bài mới nhất của mình (bonus checkin)
    // Thay vì fake like, ghi vào daily_rewards
    try {
        $d->insert('daily_rewards', [
            'user_id' => $uid,
            'reward_date' => date('Y-m-d'),
            'deliveries' => 1,
            'reward_tier' => 'checkin',
            'reward_claimed' => 1,
        ]);
    } catch (Throwable $e) {} // ignore duplicate
    
    success('+1 đơn giao thành công!', [
        'streak' => $newStreak,
        'checked_in' => true,
    ]);
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
