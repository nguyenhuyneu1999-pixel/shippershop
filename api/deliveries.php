<?php
/**
 * Đơn giao thành công API
 * 
 * GET ?action=today — Đếm đơn hôm nay (likes received on my posts)
 * GET ?action=leaderboard — Top shipper hôm nay
 * POST ?action=claim_reward — Nhận thưởng khi đạt mốc
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

// Auth helper
function dAuth() {
    if (isset($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $data = verifyJWT($m[1]);
        if ($data && isset($data['user_id'])) return intval($data['user_id']);
    }
    return 0;
}

// Reward tiers
$REWARD_TIERS = [
    ['threshold' => 10, 'name' => 'bronze', 'label' => '🥉 10 đơn', 'xp' => 20, 'desc' => 'Chăm chỉ!'],
    ['threshold' => 25, 'name' => 'silver', 'label' => '🥈 25 đơn', 'xp' => 50, 'desc' => 'Shipper giỏi!'],
    ['threshold' => 50, 'name' => 'gold', 'label' => '🥇 50 đơn', 'xp' => 100, 'desc' => 'Shipper xuất sắc!'],
    ['threshold' => 100, 'name' => 'diamond', 'label' => '💎 100 đơn', 'xp' => 200, 'desc' => 'Huyền thoại!'],
];

try {

// === TODAY: Get delivery count ===
if ($action === 'today') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    // Count likes received on MY posts today = "đơn giao thành công"
    $result = $d->fetchOne(
        "SELECT COUNT(*) as deliveries FROM post_likes pl 
         JOIN posts p ON pl.post_id = p.id 
         WHERE p.user_id = ? AND pl.created_at >= CURDATE()",
        [$uid]
    );
    $deliveries = intval($result['deliveries'] ?? 0);
    
    // Also count likes I gave (contributing to others' deliveries)
    $given = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes WHERE user_id = ? AND created_at >= CURDATE()",
        [$uid]
    );
    
    // Total all-time
    $allTime = $d->fetchOne(
        "SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ?",
        [$uid]
    );
    
    // Best day ever
    $bestDay = $d->fetchOne(
        "SELECT DATE(pl.created_at) as best_date, COUNT(*) as best_count 
         FROM post_likes pl JOIN posts p ON pl.post_id = p.id 
         WHERE p.user_id = ? 
         GROUP BY DATE(pl.created_at) 
         ORDER BY best_count DESC LIMIT 1",
        [$uid]
    );
    
    // Check reward tiers
    $claimedRewards = $d->fetchAll(
        "SELECT reward_tier FROM daily_rewards WHERE user_id = ? AND reward_date = CURDATE() AND reward_claimed = 1",
        [$uid]
    );
    $claimed = array_map(function($r) { return $r['reward_tier']; }, $claimedRewards ?: []);
    
    $tiers = [];
    foreach ($GLOBALS['REWARD_TIERS'] ?? [] as $t) { /* skip */ }
    // Use $REWARD_TIERS directly
    $availableRewards = [];
    foreach ($REWARD_TIERS as $t) {
        $tier = $t;
        $tier['reached'] = $deliveries >= $t['threshold'];
        $tier['claimed'] = in_array($t['name'], $claimed);
        $tier['can_claim'] = $tier['reached'] && !$tier['claimed'];
        $availableRewards[] = $tier;
    }
    
    // Streak info
    $streak = $d->fetchOne("SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?", [$uid]);
    
    success('OK', [
        'deliveries_today' => $deliveries,
        'given_today' => intval($given['c'] ?? 0),
        'all_time' => intval($allTime['c'] ?? 0),
        'best_day' => [
            'date' => $bestDay['best_date'] ?? null,
            'count' => intval($bestDay['best_count'] ?? 0)
        ],
        'streak' => intval($streak['current_streak'] ?? 0),
        'longest_streak' => intval($streak['longest_streak'] ?? 0),
        'rewards' => $availableRewards,
    ]);
}

// === LEADERBOARD: Top shipper today ===
if ($action === 'leaderboard') {
    $top = $d->fetchAll(
        "SELECT p.user_id, u.fullname, u.avatar, u.shipping_company,
                COUNT(*) as deliveries
         FROM post_likes pl 
         JOIN posts p ON pl.post_id = p.id
         JOIN users u ON p.user_id = u.id
         WHERE pl.created_at >= CURDATE()
         GROUP BY p.user_id
         ORDER BY deliveries DESC
         LIMIT 20"
    );
    success('OK', $top ?: []);
}

// === CLAIM REWARD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'claim_reward') {
    $uid = dAuth();
    if (!$uid) { error('Đăng nhập', 401); }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tierName = $input['tier'] ?? '';
    
    // Find tier
    $tier = null;
    foreach ($REWARD_TIERS as $t) {
        if ($t['name'] === $tierName) { $tier = $t; break; }
    }
    if (!$tier) { error('Mốc không hợp lệ'); }
    
    // Check deliveries today
    $result = $d->fetchOne(
        "SELECT COUNT(*) as deliveries FROM post_likes pl 
         JOIN posts p ON pl.post_id = p.id 
         WHERE p.user_id = ? AND pl.created_at >= CURDATE()",
        [$uid]
    );
    $deliveries = intval($result['deliveries'] ?? 0);
    
    if ($deliveries < $tier['threshold']) {
        error('Chưa đạt mốc ' . $tier['threshold'] . ' đơn (hiện có ' . $deliveries . ')');
    }
    
    // Check already claimed
    $existing = $d->fetchOne(
        "SELECT id FROM daily_rewards WHERE user_id = ? AND reward_date = CURDATE() AND reward_tier = ?",
        [$uid, $tierName]
    );
    if ($existing) { error('Đã nhận thưởng mốc này hôm nay rồi'); }
    
    // Claim!
    $d->insert('daily_rewards', [
        'user_id' => $uid,
        'reward_date' => date('Y-m-d'),
        'deliveries' => $deliveries,
        'reward_tier' => $tierName,
        'reward_claimed' => 1,
    ]);
    
    // Award XP
    try {
        if (function_exists('awardXP')) {
            awardXP($uid, 'delivery_reward', $tier['xp'], 'Thưởng ' . $tier['label']);
        }
    } catch (Throwable $e) {}
    
    success('Nhận thưởng thành công! +' . $tier['xp'] . ' XP', [
        'tier' => $tierName,
        'xp_earned' => $tier['xp'],
        'deliveries' => $deliveries,
    ]);
}

} catch (Throwable $e) {
    error('Server error: ' . $e->getMessage());
}

error('Invalid action');
