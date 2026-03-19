<?php
/**
 * GAMIFICATION API - XP, Streaks, Levels, Badges
 * GET  ?action=profile    - XP profile + level + streak
 * GET  ?action=history    - XP history
 * GET  ?action=leaders    - XP leaderboard
 * POST ?action=checkin    - Daily check-in (+5 XP)
 * POST ?action=award      - Award XP (internal, called by other APIs)
 */
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}
$d=db();$action=$_GET['action']??'';
function gOk($data=null,$msg='OK'){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function gErr($msg){echo json_encode(['success'=>false,'message'=>$msg],JSON_UNESCAPED_UNICODE);exit;}

$LEVELS = [
    1 => ['name' => 'Tân binh', 'xp' => 0, 'badge' => '🟢', 'color' => '#4CAF50'],
    2 => ['name' => 'Shipper', 'xp' => 500, 'badge' => '🟡', 'color' => '#FFC107'],
    3 => ['name' => 'Pro Shipper', 'xp' => 2000, 'badge' => '🟠', 'color' => '#FF9800'],
    4 => ['name' => 'Master', 'xp' => 5000, 'badge' => '🔴', 'color' => '#F44336'],
    5 => ['name' => 'Legend', 'xp' => 15000, 'badge' => '🟣', 'color' => '#7C3AED'],
];

function getLevel($xp) {
    global $LEVELS;
    $lv = 1;
    foreach ($LEVELS as $l => $info) { if ($xp >= $info['xp']) $lv = $l; }
    return $lv;
}

function getNextLevel($currentLevel) {
    global $LEVELS;
    $next = $currentLevel + 1;
    return isset($LEVELS[$next]) ? $LEVELS[$next] : null;
}

// ===== XP PROFILE =====
if ($action === 'profile') {
    $uid = getAuthUserId();
    $streak = $d->fetchOne("SELECT * FROM user_streaks WHERE user_id = ?", [$uid]);
    if (!$streak) {
        $d->query("INSERT INTO user_streaks (user_id) VALUES (?)", [$uid]);
        $streak = ['current_streak' => 0, 'longest_streak' => 0, 'total_xp' => 0, 'level' => 1, 'last_active_date' => null];
    }
    
    $totalXp = (int)$streak['total_xp'];
    $level = getLevel($totalXp);
    $nextLv = getNextLevel($level);
    $progress = $nextLv ? min(100, round(($totalXp - $LEVELS[$level]['xp']) / max(1, $nextLv['xp'] - $LEVELS[$level]['xp']) * 100)) : 100;
    
    // Recent XP
    $recent = $d->fetchAll("SELECT action, xp, detail, created_at FROM user_xp WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$uid]);
    
    // Today's actions count
    $todayPosts = $d->fetchOne("SELECT COUNT(*) as c FROM user_xp WHERE user_id = ? AND action = 'post' AND DATE(created_at) = CURDATE()", [$uid])['c'];
    $todayComments = $d->fetchOne("SELECT COUNT(*) as c FROM user_xp WHERE user_id = ? AND action = 'comment' AND DATE(created_at) = CURDATE()", [$uid])['c'];
    
    gOk([
        'total_xp' => $totalXp,
        'level' => $level,
        'level_info' => $LEVELS[$level],
        'next_level' => $nextLv,
        'progress' => $progress,
        'streak' => [
            'current' => (int)$streak['current_streak'],
            'longest' => (int)$streak['longest_streak'],
            'last_active' => $streak['last_active_date'],
        ],
        'today' => [
            'posts' => (int)$todayPosts,
            'comments' => (int)$todayComments,
        ],
        'recent_xp' => $recent,
        'all_levels' => $LEVELS,
    ]);
}

// ===== XP HISTORY =====
if ($action === 'history') {
    $uid = getAuthUserId();
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $items = $d->fetchAll("SELECT * FROM user_xp WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset", [$uid]);
    $total = $d->fetchOne("SELECT COUNT(*) as c FROM user_xp WHERE user_id = ?", [$uid])['c'];
    gOk(['items' => $items, 'total' => (int)$total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

// ===== LEADERBOARD =====
if ($action === 'leaders') {
    $period = $_GET['period'] ?? 'all';
    $dateFilter = '';
    if ($period === 'week') $dateFilter = "AND ux.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    elseif ($period === 'month') $dateFilter = "AND ux.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";
    
    $leaders = $d->fetchAll("SELECT u.id, u.fullname, u.avatar, u.shipping_company,
        COALESCE(SUM(ux.xp), 0) as total_xp,
        COALESCE(us.current_streak, 0) as streak,
        COALESCE(us.level, 1) as level
        FROM user_xp ux
        JOIN users u ON ux.user_id = u.id
        LEFT JOIN user_streaks us ON u.id = us.user_id
        WHERE 1=1 $dateFilter
        GROUP BY ux.user_id
        ORDER BY total_xp DESC LIMIT 20");
    
    gOk($leaders);
}

// ===== DAILY CHECK-IN =====
if ($action === 'checkin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = getAuthUserId();
    $today = date('Y-m-d');
    
    // Check if already checked in today
    $existing = $d->fetchOne("SELECT id FROM user_xp WHERE user_id = ? AND action = 'checkin' AND DATE(created_at) = ?", [$uid, $today]);
    if ($existing) gErr('Đã check-in hôm nay rồi!');
    
    // Award XP
    $d->query("INSERT INTO user_xp (user_id, action, xp, detail) VALUES (?, 'checkin', 5, ?)", [$uid, 'Check-in ngày ' . $today]);
    
    // Update streak
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak = $d->fetchOne("SELECT * FROM user_streaks WHERE user_id = ?", [$uid]);
    
    if (!$streak) {
        $d->query("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_active_date, total_xp) VALUES (?, 1, 1, ?, 5)", [$uid, $today]);
        $newStreak = 1;
    } else {
        $newStreak = ($streak['last_active_date'] === $yesterday) ? $streak['current_streak'] + 1 : 1;
        $longest = max($newStreak, $streak['longest_streak']);
        $newXp = $streak['total_xp'] + 5;
        
        // Streak bonus
        $bonus = 0;
        if ($newStreak === 7) $bonus = 50;
        elseif ($newStreak === 30) $bonus = 200;
        elseif ($newStreak % 7 === 0) $bonus = 20;
        
        if ($bonus > 0) {
            $d->query("INSERT INTO user_xp (user_id, action, xp, detail) VALUES (?, 'streak_bonus', ?, ?)",
                [$uid, $bonus, 'Streak ' . $newStreak . ' ngày!']);
            $newXp += $bonus;
        }
        
        $level = getLevel($newXp);
        $d->query("UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_active_date = ?, total_xp = ?, level = ? WHERE user_id = ?",
            [$newStreak, $longest, $today, $newXp, $level, $uid]);
    }
    
    gOk([
        'xp_earned' => 5,
        'streak' => $newStreak,
        'bonus' => $bonus ?? 0,
        'message' => $newStreak > 1 ? "🔥 Streak $newStreak ngày! +5 XP" : "✅ Check-in thành công! +5 XP"
    ]);
}

gErr('Invalid action');
