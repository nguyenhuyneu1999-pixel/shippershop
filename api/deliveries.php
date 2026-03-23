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

/**
 * ANTI-FRAUD v2: Đếm đơn giao thành công hợp lệ
 * 
 * Rules:
 * 1. Max 2 like/ngày từ cùng 1 user
 * 2. Account < 3 ngày → skip
 * 3. Mutual like > 60% → skip pair
 * 4. Self-like → skip
 * 5. CLUSTER DETECTION: nếu > 70% likes đến từ cùng 1 nhóm ≤20 users → giảm 50%
 * 6. BURST DETECTION: > 10 likes trong 5 phút → chỉ tính 3
 * 7. DIVERSITY SCORE: cần ≥ 10 unique users mới tính đủ 50 đơn
 */
function countValidDeliveries($d, $uid, $since) {
    $likes = $d->fetchAll(
        "SELECT pl.user_id as liker_id, pl.post_id, DATE(pl.created_at) as like_date,
                pl.created_at as like_time, u.created_at as liker_joined
         FROM post_likes pl 
         JOIN posts p ON pl.post_id = p.id 
         JOIN users u ON pl.user_id = u.id
         WHERE p.user_id = ? AND pl.created_at >= $since
         ORDER BY pl.created_at",
        [$uid]
    );
    
    if (!$likes) return 0;
    
    $validCount = 0;
    $likerDailyCount = [];   // [liker_id_date] => count
    $mutualCache = [];       // cache mutual check
    $uniqueUsers = [];       // track unique valid likers
    $burstWindow = [];       // timestamp tracking for burst detection
    
    foreach ($likes as $like) {
        $likerId = intval($like['liker_id']);
        $likeDate = $like['like_date'];
        $likeTime = strtotime($like['like_time']);
        
        // Rule 4: Self-like
        if ($likerId === $uid) continue;
        
        // Rule 2: Account < 3 ngày
        $joinedDays = (time() - strtotime($like['liker_joined'])) / 86400;
        if ($joinedDays < 3) continue;
        
        // Rule 1: Max 2 like/ngày từ cùng 1 user
        $key = $likerId . '_' . $likeDate;
        if (!isset($likerDailyCount[$key])) $likerDailyCount[$key] = 0;
        $likerDailyCount[$key]++;
        if ($likerDailyCount[$key] > 2) continue;
        
        // Rule 3: Mutual like > 60%
        if (!isset($mutualCache[$likerId])) {
            $mutualCache[$likerId] = checkMutualLike($d, $uid, $likerId);
        }
        if ($mutualCache[$likerId]) continue;
        
        // Rule 6: BURST — > 10 likes trong 5 phút → chỉ tính 3
        $burstWindow[] = $likeTime;
        // Remove likes older than 5 min from window
        $burstWindow = array_filter($burstWindow, function($t) use ($likeTime) {
            return ($likeTime - $t) <= 300;
        });
        $burstWindow = array_values($burstWindow);
        if (count($burstWindow) > 10) {
            // Burst detected — only count if among first 3 in window
            static $burstDateCounted = [];
            $burstKey = $likeDate . '_burst';
            if (!isset($burstDateCounted[$burstKey])) $burstDateCounted[$burstKey] = 0;
            $burstDateCounted[$burstKey]++;
            if ($burstDateCounted[$burstKey] > 3) continue;
        }
        
        $validCount++;
        $uniqueUsers[$likerId] = true;
    }
    
    // Rule 5: CLUSTER — nếu > 70% likes từ ≤ 20 users → giảm 50%
    $uniqueCount = count($uniqueUsers);
    if ($validCount > 20 && $uniqueCount <= 20) {
        // Check concentration: top 20 users chiếm bao nhiêu %
        $userCounts = [];
        foreach ($likes as $like) {
            $lid = intval($like['liker_id']);
            if ($lid === $uid) continue;
            if (!isset($userCounts[$lid])) $userCounts[$lid] = 0;
            $userCounts[$lid]++;
        }
        arsort($userCounts);
        $top20 = array_slice($userCounts, 0, 20, true);
        $top20Total = array_sum($top20);
        $allTotal = array_sum($userCounts);
        
        if ($allTotal > 0 && ($top20Total / $allTotal) > 0.7) {
            $validCount = intval($validCount * 0.5); // Giảm 50%
        }
    }
    
    // Rule 7: DIVERSITY — cần ≥ 10 unique users cho 50 đơn
    // Nếu < 10 unique users → cap tại (uniqueUsers * 3)
    if ($uniqueCount < 10 && $validCount > ($uniqueCount * 3)) {
        $validCount = $uniqueCount * 3;
    }
    
    // Bonus: check-in hôm nay = +1 đơn
    if ($since === 'CURDATE()') {
        $checkinBonus = $d->fetchOne(
            "SELECT COUNT(*) as c FROM daily_rewards WHERE user_id = ? AND reward_date = CURDATE() AND reward_tier = 'checkin'",
            [$uid]
        );
        $validCount += intval($checkinBonus['c'] ?? 0);
    }
    
    return $validCount;
}

/**
 * Check mutual like abuse
 * Nếu A like B >= 60% bài VÀ B like A >= 60% bài → collusion
 */
function checkMutualLike($d, $userA, $userB) {
    // A's posts liked by B (last 30 days)
    $aPosts = $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND `status` = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$userA]);
    $bLikesA = $d->fetchOne("SELECT COUNT(DISTINCT pl.post_id) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.user_id = ? AND pl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$userA, $userB]);
    
    // B's posts liked by A
    $bPosts = $d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND `status` = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$userB]);
    $aLikesB = $d->fetchOne("SELECT COUNT(DISTINCT pl.post_id) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.user_id = ? AND pl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$userB, $userA]);
    
    $totalA = intval($aPosts['c'] ?? 0);
    $totalB = intval($bPosts['c'] ?? 0);
    $bToA = intval($bLikesA['c'] ?? 0);
    $aToB = intval($aLikesB['c'] ?? 0);
    
    // Need at least 5 posts each to check ratio
    if ($totalA < 5 || $totalB < 5) return false;
    
    $ratioBA = $bToA / $totalA;  // B likes what % of A's posts
    $ratioAB = $aToB / $totalB;  // A likes what % of B's posts
    
    // Both directions > 60% = collusion
    return ($ratioBA > 0.6 && $ratioAB > 0.6);
}


try {

// === TODAY ===
if ($action === 'today') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    // Đơn hôm nay (ANTI-FRAUD: chống like chéo)
    // Rule 1: 1 user tối đa 2 like/ngày cho cùng 1 người
    // Rule 2: Tài khoản < 3 ngày không tính
    // Rule 3: Mutual like ratio > 60% không tính cặp đó
    $todayCount = countValidDeliveries($d, $uid, 'CURDATE()');
    
    // Đơn tháng này (ANTI-FRAUD)
    $monthCount = countValidDeliveries($d, $uid, "DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    
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
    
    $todayCount = countValidDeliveries($d, $uid, 'CURDATE()');
    
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
    
    $monthCount = countValidDeliveries($d, $uid, "DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    
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
