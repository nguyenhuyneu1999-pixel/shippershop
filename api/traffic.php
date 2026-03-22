<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/../includes/api-error-handler.php';
try { require_once __DIR__ . '/../includes/redis-rate-limiter.php'; apiRateLimit('traffic.php', 120); } catch (Throwable $e) {}
require_once __DIR__ . '/../includes/image-optimizer.php';
setupApiErrorHandler();
// auth handled by tAuth()

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function tSuccess($msg, $data = []) { $j=json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); if(isset($GLOBALS['_ssCacheKey'])&&function_exists('_ssCacheSave'))_ssCacheSave($j); echo $j; exit; }
function tError($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

function tAuth() {
    if (isset($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $headers = getallheaders();
    $h = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $data = verifyJWT($m[1]);
        if ($data && isset($data['user_id'])) {
            $_SESSION['user_id'] = $data['user_id'];
            return intval($data['user_id']);
        }
    }
    return null;
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Auto-expire old alerts
try {
    $db->query("UPDATE traffic_alerts SET `status`='expired' WHERE `status`='active' AND expires_at < NOW()", []);
} catch (Throwable $e) { /* ignore */ }

// ==========================================
// GET - List alerts
// ==========================================
if ($method === 'GET' && empty($action)) {
    api_try_cache('traffic_' . md5(json_encode($_GET)), 20);
    $cat = $_GET['category'] ?? '';
    
    $where = "a.`status`='active' AND a.expires_at > NOW()";
    $params = [];
    if ($cat && $cat !== 'all') {
        $where .= " AND a.category = ?";
        $params[] = $cat;
    }
    
    $alerts = $db->fetchAll("SELECT a.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company FROM traffic_alerts a JOIN users u ON a.user_id = u.id WHERE $where ORDER BY a.created_at DESC LIMIT 20", $params);
    if (!$alerts) $alerts = [];
    
    foreach ($alerts as &$a) {
        $exp = strtotime($a['expires_at']); $now = time(); $rem = $exp - $now;
        if ($rem > 3600) $a['time_left'] = floor($rem/3600).'h '.floor(($rem%3600)/60).'m';
        elseif ($rem > 60) $a['time_left'] = floor($rem/60).' phút';
        else $a['time_left'] = 'Sắp hết';
        $a['reliability'] = ($a['confirms']+1)/($a['confirms']+$a['denies']+1)*100;
    }
    
    tSuccess('OK', $alerts);
}

// ==========================================
// GET - Single alert
// ==========================================
if ($method === 'GET' && $action === 'detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) tError('Missing id');
    $a = $db->fetchOne("SELECT a.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company FROM traffic_alerts a JOIN users u ON a.user_id=u.id WHERE a.id=?", [$id]);
    if (!$a) tError('Not found', 404);
    tSuccess('OK', $a);
}

// ==========================================
// POST - Create alert
// ==========================================
if ($method === 'POST' && empty($action)) {
    $uid = tAuth();
    if (!$uid) tError('Đăng nhập để báo cáo', 401);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    $cat = $input['category'] ?? 'traffic';
    $content = trim($input['content'] ?? '');
    $lat = floatval($input['latitude'] ?? 0);
    $lng = floatval($input['longitude'] ?? 0);
    $address = trim($input['address'] ?? '');
    $province = $input['province'] ?? null;
    $district = $input['district'] ?? null;
    $severity = $input['severity'] ?? 'medium';
    $isQuick = intval($input['is_quick'] ?? 0);
    $images = $input['images'] ?? '[]';
    $videoUrl = $input['video_url'] ?? null;
    $duration = intval($input['duration'] ?? 60); // minutes, default 1h

    // No validation required - allow posting with any info

    // Cap duration: 30min - 2h
    $duration = max(30, min(120, $duration));
    $expiresAt = date('Y-m-d H:i:s', time() + $duration * 60);

    if (is_array($images)) $images = json_encode($images);

    // Quick alert: auto-generate content
    if ($isQuick && empty($content)) {
        $catNames = ['traffic'=>'Ùn tắc giao thông','weather'=>'Cảnh báo thời tiết','terrain'=>'Địa hình nguy hiểm','warning'=>'Cảnh báo nguy hiểm','other'=>'Cảnh báo khác'];
        $content = '⚠️ ' . ($catNames[$cat] ?? 'Cảnh báo') . ($address ? "\n📍 " . $address : '');
    }

    $db->query("INSERT INTO traffic_alerts (user_id,category,content,images,video_url,latitude,longitude,address,province,district,severity,expires_at,is_quick,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
        [$uid, $cat, $content, $images, $videoUrl, $lat ?: null, $lng ?: null, $address, $province, $district, $severity, $expiresAt, $isQuick, 'active']);

    $alertId = $db->getLastInsertId();

    // Increase trust score
    try { $db->query("UPDATE users SET trust_score = trust_score + 1 WHERE id = ?", [$uid]); } catch (Throwable $e) {}

    // Push: notify followers about new traffic alert
    try {
        require_once __DIR__.'/../includes/push-helper.php';
        $me = $db->fetchOne("SELECT fullname FROM users WHERE id = ?", [$uid]);
        $mName = $me ? $me['fullname'] : 'Ai đó';
        $catNames = ['traffic'=>'Ùn tắc','weather'=>'Thời tiết','terrain'=>'Địa hình','warning'=>'Nguy hiểm','other'=>'Cảnh báo'];
        $catLabel = $catNames[$cat] ?? 'Giao thông';
        $preview = mb_substr($content, 0, 60);
        $followers = $db->fetchAll("SELECT follower_id FROM follows WHERE following_id = ? LIMIT 50", [$uid]);
        foreach ($followers as $f) {
            notifyUser(intval($f['follower_id']), 'Giao thông: ' . $catLabel, $mName . ': ' . $preview, 'traffic', '/traffic.html');
        }
    } catch (Throwable $e) {}

    tSuccess('Đã báo cáo!', ['id' => $alertId, 'expires_at' => $expiresAt]);
}

// ==========================================
// POST - Confirm/Deny
// ==========================================
if ($method === 'POST' && $action === 'vote') {
    $uid = tAuth();
    if (!$uid) tError('Đăng nhập', 401);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $alertId = intval($input['alert_id'] ?? 0);
    $vote = $input['vote'] ?? 'confirm'; // confirm or deny

    if (!$alertId) tError('Missing alert_id');
    if (!in_array($vote, ['confirm', 'deny'])) tError('Invalid vote');

    // Check existing vote
    $existing = $db->fetchOne("SELECT id, vote FROM traffic_confirms WHERE alert_id=? AND user_id=?", [$alertId, $uid]);

    if ($existing) {
        if ($existing['vote'] === $vote) {
            // Remove vote
            $db->query("DELETE FROM traffic_confirms WHERE id=?", [$existing['id']]);
            $col = $vote === 'confirm' ? 'confirms' : 'denies';
            $db->query("UPDATE traffic_alerts SET $col = GREATEST(0, $col - 1) WHERE id=?", [$alertId]);
            tSuccess('Đã bỏ', ['voted' => null]);
        } else {
            // Change vote
            $db->query("UPDATE traffic_confirms SET vote=? WHERE id=?", [$vote, $existing['id']]);
            if ($vote === 'confirm') {
                $db->query("UPDATE traffic_alerts SET confirms=confirms+1, denies=GREATEST(0,denies-1) WHERE id=?", [$alertId]);
            } else {
                $db->query("UPDATE traffic_alerts SET denies=denies+1, confirms=GREATEST(0,confirms-1) WHERE id=?", [$alertId]);
            }
            tSuccess('Đã đổi', ['voted' => $vote]);
        }
    } else {
        $db->query("INSERT INTO traffic_confirms (alert_id,user_id,vote) VALUES (?,?,?)", [$alertId, $uid, $vote]);
        $col = $vote === 'confirm' ? 'confirms' : 'denies';
        $db->query("UPDATE traffic_alerts SET $col = $col + 1 WHERE id=?", [$alertId]);

        // If too many denies, auto-remove
        $alert = $db->fetchOne("SELECT confirms, denies FROM traffic_alerts WHERE id=?", [$alertId]);
        if ($alert && $alert['denies'] >= 5 && $alert['denies'] > $alert['confirms'] * 2) {
            $db->query("UPDATE traffic_alerts SET `status`='removed' WHERE id=?", [$alertId]);
        }

        tSuccess('OK', ['voted' => $vote]);
    }
}

// ==========================================
// POST - Upload image
// ==========================================
if ($method === 'POST' && $action === 'upload') {
    $uid = tAuth();
    if (!$uid) tError('Đăng nhập', 401);

    $uploadDir = __DIR__ . '/../uploads/traffic/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (empty($_FILES['image'])) tError('Chọn ảnh');
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) tError('Lỗi upload');

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) tError('Chỉ chấp nhận ảnh');

    $fname = 'tf_' . $uid . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fname)) tError('Lỗi lưu file');

    tSuccess('OK', ['url' => '/uploads/traffic/' . $fname]);
}


// ==========================================
// GET - Comments for alert
// ==========================================
if ($method === 'GET' && $action === 'comments') {
    $alertId = intval($_GET['alert_id'] ?? 0);
    if (!$alertId) tError('Missing alert_id');
    
    // Auto-create table if not exists
    try {
        $db->query("CREATE TABLE IF NOT EXISTS traffic_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_alert (alert_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
    } catch (Throwable $e) {}
    
    $cmts = $db->fetchAll("SELECT c.*, u.fullname as user_name, u.avatar as user_avatar FROM traffic_comments c JOIN users u ON c.user_id=u.id WHERE c.alert_id=? ORDER BY c.created_at ASC", [$alertId]);
    if (!$cmts) $cmts = [];
    tSuccess('OK', $cmts);
}

// ==========================================
// POST - Add comment
// ==========================================
if ($method === 'POST' && $action === 'comment') {
    $uid = tAuth();
    if (!$uid) tError('Đăng nhập', 401);
    
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $alertId = intval($input['alert_id'] ?? 0);
    $ct = trim($input['content'] ?? '');
    
    if (!$alertId || !$ct) tError('Thiếu thông tin');
    
    // Auto-create table if not exists
    try {
        $db->query("CREATE TABLE IF NOT EXISTS traffic_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_alert (alert_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []);
    } catch (Throwable $e) {}
    
    $db->query("INSERT INTO traffic_comments (alert_id,user_id,content,created_at) VALUES (?,?,?,NOW())", [$alertId, $uid, $ct]);
    tSuccess('OK');
}

tError('Invalid request');
