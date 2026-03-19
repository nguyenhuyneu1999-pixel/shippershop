<?php
// ShipperShop Analytics - Self-hosted page view tracker
// Tracks: page views, unique visitors, referrers, devices
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../includes/db.php';
$d = db();

// Create tables on first run
try {
    $d->query("CREATE TABLE IF NOT EXISTS analytics_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(200) NOT NULL,
        referrer VARCHAR(500) DEFAULT NULL,
        user_agent VARCHAR(500) DEFAULT NULL,
        ip_hash VARCHAR(64) NOT NULL,
        session_id VARCHAR(64) DEFAULT NULL,
        user_id INT DEFAULT NULL,
        device VARCHAR(20) DEFAULT 'mobile',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(page), INDEX(created_at), INDEX(ip_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Throwable $e) {}

$action = $_GET['action'] ?? '';

// Track page view
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'view') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $page = substr(trim($input['page'] ?? '/'), 0, 200);
    $referrer = substr(trim($input['referrer'] ?? ''), 0, 500);
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = hash('sha256', $ip . date('Y-m-d')); // Daily unique hash
    $sessionId = substr(trim($input['sid'] ?? ''), 0, 64);
    $userId = intval($input['uid'] ?? 0) ?: null;
    
    // Detect device
    $device = 'desktop';
    if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) $device = 'mobile';
    elseif (preg_match('/Tablet|iPad/i', $ua)) $device = 'tablet';
    
    // Don't track bots
    if (preg_match('/bot|crawler|spider|curl|wget/i', $ua)) {
        echo json_encode(['success' => true, 'tracked' => false]);
        exit;
    }
    
    // Rate limit: max 1 view per page per IP per 5 minutes
    $recent = $d->fetchOne("SELECT id FROM analytics_views WHERE ip_hash = ? AND page = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)", [$ipHash, $page]);
    if ($recent) {
        echo json_encode(['success' => true, 'tracked' => false, 'reason' => 'rate_limit']);
        exit;
    }
    
    $d->query("INSERT INTO analytics_views (page, referrer, user_agent, ip_hash, session_id, user_id, device) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$page, $referrer ?: null, $ua, $ipHash, $sessionId ?: null, $userId, $device]);
    
    echo json_encode(['success' => true, 'tracked' => true]);
    exit;
}

// Get stats (public, no auth needed)
if ($action === 'stats') {
    $period = $_GET['period'] ?? '7d';
    $days = $period === '30d' ? 30 : ($period === '24h' ? 1 : 7);
    
    $totalViews = $d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)")['c'];
    $uniqueVisitors = $d->fetchOne("SELECT COUNT(DISTINCT ip_hash) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)")['c'];
    
    $topPages = $d->fetchAll("SELECT page, COUNT(*) as views FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY page ORDER BY views DESC LIMIT 10");
    
    $topReferrers = $d->fetchAll("SELECT referrer, COUNT(*) as views FROM analytics_views WHERE referrer IS NOT NULL AND referrer != '' AND created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY referrer ORDER BY views DESC LIMIT 10");
    
    $deviceBreakdown = $d->fetchAll("SELECT device, COUNT(*) as views FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY device");
    
    $dailyViews = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as views, COUNT(DISTINCT ip_hash) as unique_visitors FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'period' => $period,
            'total_views' => intval($totalViews),
            'unique_visitors' => intval($uniqueVisitors),
            'top_pages' => $topPages,
            'top_referrers' => $topReferrers,
            'devices' => $deviceBreakdown,
            'daily' => $dailyViews
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
