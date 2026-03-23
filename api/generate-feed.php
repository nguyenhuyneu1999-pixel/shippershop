<?php
/**
 * Pre-generate static feed JSON files
 * Called by cron every 30 seconds
 * Writes to /api/static/ as .json files (served by LiteSpeed, no PHP)
 * 
 * cPanel cron: * * * * * curl -s https://shippershop.vn/api/generate-feed.php?key=ss_gen_feed >/dev/null
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (($_GET['key'] ?? '') !== 'ss_gen_feed') { http_response_code(403); exit; }

$db = db();
$dir = __DIR__ . '/static';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$start = microtime(true);
$generated = [];

// === Generate feed pages (new, hot, trending) ===
$sorts = [
    'new' => 'p.created_at DESC',
    'hot' => 'p.hot_score DESC, p.created_at DESC',
    'top' => 'p.likes_count DESC, p.created_at DESC',
];

foreach ($sorts as $sortName => $orderBy) {
    $posts = $db->fetchAll(
        "SELECT p.id, p.user_id, p.content, p.images, p.type, p.likes_count, p.comments_count, p.shares_count, p.views_count, p.hot_score, p.created_at, p.video_url, p.district, p.province, p.edited_at, p.scheduled_at, p.is_draft,
                u.fullname as user_name, u.avatar as user_avatar, u.username as user_username, u.shipping_company,
                sp.badge as sub_badge, sp.badge_color as sub_badge_color
         FROM posts p
         LEFT JOIN users u ON p.user_id = u.id
         LEFT JOIN user_subscriptions us2 ON us2.user_id = p.user_id AND us2.`status` = 'active' AND us2.expires_at > NOW()
         LEFT JOIN subscription_plans sp ON sp.id = us2.plan_id AND sp.price > 0
         WHERE p.`status` = 'active' AND (p.scheduled_at IS NULL OR p.scheduled_at <= NOW()) AND (p.is_draft = 0 OR p.is_draft IS NULL)
         ORDER BY $orderBy
         LIMIT 40", []
    );
    
    $data = [
        'success' => true,
        'data' => ['posts' => $posts ?: [], 'total' => count($posts ?: [])],
        'generated_at' => date('Y-m-d H:i:s'),
        'sort' => $sortName,
    ];
    
    // Remove nulls to slim JSON
    foreach ($data['data']['posts'] as &$p) {
        $p = array_filter($p, function($v) { return $v !== null && $v !== ''; });
    }
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    file_put_contents($dir . '/feed-' . $sortName . '.json', $json);
    $generated[] = 'feed-' . $sortName . '.json (' . strlen($json) . ' bytes)';
}

// === Generate trending hashtags ===
$posts24h = $db->fetchAll("SELECT content FROM posts WHERE `status` = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)", []);
$tags = [];
foreach ($posts24h ?: [] as $post) {
    preg_match_all('/#([a-zA-ZÀ-ỹ0-9_]+)/u', $post['content'], $matches);
    foreach ($matches[1] ?? [] as $tag) { $t = mb_strtolower($tag); $tags[$t] = ($tags[$t] ?? 0) + 1; }
}
arsort($tags);
$topTags = [];
foreach (array_slice($tags, 0, 10, true) as $tag => $count) { $topTags[] = ['tag' => $tag, 'count' => $count]; }
file_put_contents($dir . '/trending.json', json_encode(['success' => true, 'data' => $topTags], JSON_UNESCAPED_UNICODE));
$generated[] = 'trending.json';

// === Generate group discover ===
$groups = $db->fetchAll("SELECT g.id, g.name, g.description, g.icon_image, g.cover_image, g.member_count, g.post_count, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.`status` = 'active' ORDER BY g.member_count DESC LIMIT 20", []);
file_put_contents($dir . '/groups-discover.json', json_encode(['success' => true, 'data' => $groups ?: []], JSON_UNESCAPED_UNICODE));
$generated[] = 'groups-discover.json';

$ms = round((microtime(true) - $start) * 1000);
echo json_encode(['success' => true, 'generated' => $generated, 'time_ms' => $ms]);
