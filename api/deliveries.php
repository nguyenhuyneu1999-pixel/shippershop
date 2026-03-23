<?php
/**
 * Đơn giao thành công API
 * 
 * Like trên bài = 1 đơn giao thành công
 * 50 đơn/ngày → thưởng 5.000đ vào ví
 * 1.300 đơn/tháng → thưởng 100.000đ vào ví
 * Reset 00:00 mỗi ngày
 * 
 * GET ?action=today — đếm đơn hôm nay + tháng
 * GET ?action=leaderboard — top shipper hôm nay
 * POST ?action=claim_daily — nhận thưởng ngày (50 đơn)
 * POST ?action=claim_monthly — nhận thưởng tháng (1300 đơn)
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

$d = db();
$action = $_GET['action'] ?? 'today';

function dAuth() {
    if (isset($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $data = verifyJWT($m[1]);
        if ($data && isset($data['user_id'])) return intval($data['user_id']);
    }
    return 0;
}

// Constants
$DAILY_TARGET = 50;
$DAILY_REWARD = 5000;       // 5.000đ
$MONTHLY_TARGET = 1300;
$MONTHLY_REWARD = 100000;   // 100.000đ

try {

// === TODAY ===
if ($action === 'today') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    // Đơn hôm nay (likes received on my posts today)
    $today = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.created_at >= CURDATE()",
        [$uid]
    );
    $todayCount = intval($today['c'] ?? 0);
    
    // Đơn tháng này
    $month = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        [$uid]
    );
    $monthCount = intval($month['c'] ?? 0);
    
    // Tổng all-time
    $allTime = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ?",
        [$uid]
    );
    
    // Kỷ lục ngày
    $bestDay = $d->fetchOne(
        "SELECT DATE(pl.created_at) as best_date, COUNT(*) as best_count FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? GROUP BY DATE(pl.created_at) ORDER BY best_count DESC LIMIT 1",
        [$uid]
    );
    
    // Đã nhận thưởng ngày chưa?
    $dailyClaimed = $d->fetchOne(
        "SELECT id FROM daily_rewards WHERE user_id = ? AND reward_date = CURDATE() AND reward_tier = 'daily'",
        [$uid]
    );
    
    // Đã nhận thưởng tháng chưa?
    $monthKey = date('Y-m');
    $monthlyClaimed = $d->fetchOne(
        "SELECT id FROM daily_rewards WHERE user_id = ? AND reward_tier = 'monthly' AND reward_date LIKE ?",
        [$uid, $monthKey . '%']
    );
    
    // Streak (ngày liên tiếp đạt 50 đơn)
    $streak = $d->fetchOne("SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?", [$uid]);
    
    // Wallet balance
    $wallet = $d->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$uid]);
    
    success('OK', [
        'today' => $todayCount,
        'today_target' => $DAILY_TARGET,
        'today_progress' => min(100, round($todayCount / $DAILY_TARGET * 100)),
        'today_reward' => $DAILY_REWARD,
        'today_claimed' => $dailyClaimed ? true : false,
        'can_claim_daily' => $todayCount >= $DAILY_TARGET && !$dailyClaimed,
        
        'month' => $monthCount,
        'month_target' => $MONTHLY_TARGET,
        'month_progress' => min(100, round($monthCount / $MONTHLY_TARGET * 100)),
        'month_reward' => $MONTHLY_REWARD,
        'month_claimed' => $monthlyClaimed ? true : false,
        'can_claim_monthly' => $monthCount >= $MONTHLY_TARGET && !$monthlyClaimed,
        'month_label' => date('m/Y'),
        
        'all_time' => intval($allTime['c'] ?? 0),
        'best_day' => intval($bestDay['best_count'] ?? 0),
        'streak' => intval($streak['current_streak'] ?? 0),
        'balance' => intval($wallet['balance'] ?? 0),
        'days_left' => intval(date('t')) - intval(date('j')),
    ]);
}

// === LEADERBOARD ===
if ($action === 'leaderboard') {
    $top = $d->fetchAll(
        "SELECT p.user_id, u.fullname, u.avatar, u.shipping_company, COUNT(*) as deliveries
         FROM post_likes pl JOIN posts p ON pl.post_id = p.id JOIN users u ON p.user_id = u.id
         WHERE pl.created_at >= CURDATE()
         GROUP BY p.user_id ORDER BY deliveries DESC LIMIT 20"
    );
    success('OK', $top ?: []);
}

// === CLAIM DAILY (50 đơn → 5.000đ) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_daily') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    $today = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.created_at >= CURDATE()",
        [$uid]
    );
    $todayCount = intval($today['c'] ?? 0);
    
    if ($todayCount < $DAILY_TARGET) {
        error('Chưa đạt ' . $DAILY_TARGET . ' đơn (hiện có ' . $todayCount . ')');
    }
    
    $existing = $d->fetchOne(
        "SELECT id FROM daily_rewards WHERE user_id = ? AND reward_date = CURDATE() AND reward_tier = 'daily'",
        [$uid]
    );
    if ($existing) { error('Đã nhận thưởng ngày hôm nay rồi'); }
    
    // Begin transaction
    $pdo = $d->getConnection();
    $pdo->beginTransaction();
    try {
        // Record reward
        $d->insert('daily_rewards', [
            'user_id' => $uid,
            'reward_date' => date('Y-m-d'),
            'deliveries' => $todayCount,
            'reward_tier' => 'daily',
            'reward_claimed' => 1,
        ]);
        
        // Add to wallet
        $wallet = $d->fetchOne("SELECT id, balance FROM wallets WHERE user_id = ?", [$uid]);
        if ($wallet) {
            $d->query("UPDATE wallets SET balance = balance + ? WHERE user_id = ?", [$DAILY_REWARD, $uid]);
        } else {
            $d->insert('wallets', ['user_id' => $uid, 'balance' => $DAILY_REWARD]);
        }
        
        // Transaction log
        $d->insert('wallet_transactions', [
            'user_id' => $uid,
            'type' => 'reward',
            'amount' => $DAILY_REWARD,
            'description' => 'Thưởng ' . number_format($DAILY_REWARD) . 'đ - ' . $todayCount . ' đơn giao thành công ngày ' . date('d/m'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        $pdo->commit();
        
        $newBalance = $d->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$uid]);
        success('Nhận ' . number_format($DAILY_REWARD) . 'đ thành công!', [
            'reward' => $DAILY_REWARD,
            'balance' => intval($newBalance['balance'] ?? 0),
        ]);
    } catch (Throwable $e) {
        $pdo->rollback();
        error('Lỗi: ' . $e->getMessage());
    }
}

// === CLAIM MONTHLY (1.300 đơn → 100.000đ) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_monthly') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    $month = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        [$uid]
    );
    $monthCount = intval($month['c'] ?? 0);
    
    if ($monthCount < $MONTHLY_TARGET) {
        error('Chưa đạt ' . number_format($MONTHLY_TARGET) . ' đơn tháng (hiện có ' . number_format($monthCount) . ')');
    }
    
    $monthKey = date('Y-m');
    $existing = $d->fetchOne(
        "SELECT id FROM daily_rewards WHERE user_id = ? AND reward_tier = 'monthly' AND reward_date LIKE ?",
        [$uid, $monthKey . '%']
    );
    if ($existing) { error('Đã nhận thưởng tháng ' . date('m/Y') . ' rồi'); }
    
    $pdo = $d->getConnection();
    $pdo->beginTransaction();
    try {
        $d->insert('daily_rewards', [
            'user_id' => $uid,
            'reward_date' => date('Y-m-d'),
            'deliveries' => $monthCount,
            'reward_tier' => 'monthly',
            'reward_claimed' => 1,
        ]);
        
        $wallet = $d->fetchOne("SELECT id FROM wallets WHERE user_id = ?", [$uid]);
        if ($wallet) {
            $d->query("UPDATE wallets SET balance = balance + ? WHERE user_id = ?", [$MONTHLY_REWARD, $uid]);
        } else {
            $d->insert('wallets', ['user_id' => $uid, 'balance' => $MONTHLY_REWARD]);
        }
        
        $d->insert('wallet_transactions', [
            'user_id' => $uid,
            'type' => 'reward',
            'amount' => $MONTHLY_REWARD,
            'description' => 'Thưởng tháng ' . date('m/Y') . ' - ' . number_format($monthCount) . ' đơn giao thành công',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        $pdo->commit();
        
        $newBalance = $d->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$uid]);
        success('Nhận ' . number_format($MONTHLY_REWARD) . 'đ thành công!', [
            'reward' => $MONTHLY_REWARD,
            'balance' => intval($newBalance['balance'] ?? 0),
        ]);
    } catch (Throwable $e) {
        $pdo->rollback();
        error('Lỗi: ' . $e->getMessage());
    }
}

} catch (Throwable $e) {
    error('Server error');
}

error('Invalid action');
