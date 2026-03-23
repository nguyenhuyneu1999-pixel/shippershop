<?php
/**
 * ShipperShop Daily Check-in
 * POST ?action=checkin — Check in today (earn XP)
 * GET ?action=status — Check-in status + streak
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../includes/xp-helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();
$action = $_GET['action'] ?? '';

if ($action === 'status') {
    $uid = getOptionalAuthUserId();
    if (!$uid) { success('OK', ['checked_in' => false, 'streak' => 0]); }
    
    $streak = $d->fetchOne("SELECT current_streak, last_active_date FROM user_streaks WHERE user_id = ?", [$uid]);
    $checkedToday = false;
    $currentStreak = 0;
    
    if ($streak) {
        $checkedToday = ($streak['last_active_date'] === date('Y-m-d'));
        $currentStreak = intval($streak['current_streak']);
        // Reset if missed yesterday
        if (!$checkedToday && $streak['last_active_date'] !== date('Y-m-d', strtotime('-1 day'))) {
            $currentStreak = 0;
        }
    }
    
    success('OK', [
        'checked_in' => $checkedToday,
        'streak' => $currentStreak,
        'xp_reward' => min(10 + $currentStreak * 2, 50),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'checkin') {
    $uid = getAuthUserId();
    if (!$uid) { error('Auth required', 401); }
    
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
    
    // Award XP (more for longer streaks, max 50)
    $xp = min(10 + ($newStreak - 1) * 2, 50);
    try { awardXP($uid, 'checkin', $xp, 'Điểm danh ngày ' . date('d/m')); } catch (Throwable $e) {}
    
    success('Điểm danh thành công! +' . $xp . ' XP', [
        'streak' => $newStreak,
        'xp_earned' => $xp,
        'checked_in' => true,
    ]);
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
