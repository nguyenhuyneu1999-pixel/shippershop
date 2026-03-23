<?php
/**
 * ShipperShop Achievements
 * GET ?action=list — All available achievements
 * GET ?action=user&id=X — User's earned achievements
 * POST ?action=check — Check and award new achievements
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d = db();
$action = $_GET['action'] ?? 'list';

// Achievement definitions
$achievements = [
    ['id'=>'first_post','name'=>'Bài viết đầu tiên','icon'=>'📝','desc'=>'Đăng bài viết đầu tiên','condition'=>'posts >= 1'],
    ['id'=>'post_10','name'=>'Người viết tích cực','icon'=>'✍️','desc'=>'Đăng 10 bài viết','condition'=>'posts >= 10'],
    ['id'=>'post_50','name'=>'Cây viết vàng','icon'=>'🏆','desc'=>'Đăng 50 bài viết','condition'=>'posts >= 50'],
    ['id'=>'like_100','name'=>'Được yêu thích','icon'=>'❤️','desc'=>'Nhận 100 lượt thành công','condition'=>'likes >= 100'],
    ['id'=>'streak_7','name'=>'7 ngày liên tiếp','icon'=>'🔥','desc'=>'Điểm danh 7 ngày liên tiếp','condition'=>'streak >= 7'],
    ['id'=>'streak_30','name'=>'Shipper kiên trì','icon'=>'💪','desc'=>'Điểm danh 30 ngày liên tiếp','condition'=>'streak >= 30'],
    ['id'=>'comment_50','name'=>'Người giúp đỡ','icon'=>'💬','desc'=>'Ghi chú 50 lần','condition'=>'comments >= 50'],
    ['id'=>'group_join','name'=>'Thành viên cộng đồng','icon'=>'👥','desc'=>'Tham gia nhóm đầu tiên','condition'=>'groups >= 1'],
    ['id'=>'follower_10','name'=>'Influencer nhí','icon'=>'⭐','desc'=>'Có 10 người theo dõi','condition'=>'followers >= 10'],
    ['id'=>'follower_100','name'=>'Shipper nổi tiếng','icon'=>'🌟','desc'=>'Có 100 người theo dõi','condition'=>'followers >= 100'],
];

if ($action === 'list') {
    api_try_cache('achievements_list', 3600);
    success('OK', ['achievements' => $achievements]);
}

if ($action === 'user') {
    $uid = intval($_GET['id'] ?? 0);
    if (!$uid) { error('Missing id'); }
    api_try_cache('achievements_user_' . $uid, 120);
    
    $earned = $d->fetchAll("SELECT badge_id, earned_at FROM user_badges WHERE user_id = ?", [$uid]);
    $earnedIds = array_column($earned ?: [], 'badge_id');
    $earnedMap = [];
    foreach ($earned ?: [] as $e) $earnedMap[$e['badge_id']] = $e['earned_at'];
    
    $result = [];
    foreach ($achievements as $a) {
        $a['earned'] = in_array($a['id'], $earnedIds);
        $a['earned_at'] = $earnedMap[$a['id']] ?? null;
        $result[] = $a;
    }
    
    success('OK', ['achievements' => $result, 'earned_count' => count(array_filter($result, function($a) { return $a['earned']; }))]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'check') {
    $uid = getAuthUserId();
    if (!$uid) { error('Auth required', 401); }
    
    // Get user stats
    $posts = intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND `status` = 'active'", [$uid])['c']);
    $likes = intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM posts WHERE user_id = ? AND `status` = 'active'", [$uid])['c']);
    $comments = intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id = ? AND `status` = 'active'", [$uid])['c']);
    $groups = intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id = ?", [$uid])['c']);
    $followers = intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id = ?", [$uid])['c']);
    $streak = intval($d->fetchOne("SELECT COALESCE(longest_streak,0) as c FROM user_streaks WHERE user_id = ?", [$uid])['c'] ?? 0);
    
    $earned = array_column($d->fetchAll("SELECT badge_id FROM user_badges WHERE user_id = ?", [$uid]) ?: [], 'badge_id');
    $newBadges = [];
    
    $checks = [
        'first_post' => $posts >= 1, 'post_10' => $posts >= 10, 'post_50' => $posts >= 50,
        'like_100' => $likes >= 100, 'streak_7' => $streak >= 7, 'streak_30' => $streak >= 30,
        'comment_50' => $comments >= 50, 'group_join' => $groups >= 1,
        'follower_10' => $followers >= 10, 'follower_100' => $followers >= 100,
    ];
    
    foreach ($checks as $id => $met) {
        if ($met && !in_array($id, $earned)) {
            try {
                $d->query("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())", [$uid, $id]);
                $newBadges[] = $id;
            } catch (Throwable $e) {}
        }
    }
    
    api_cache_flush('achievements_user_');
    success('OK', ['new_badges' => $newBadges, 'total_earned' => count($earned) + count($newBadges)]);
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
