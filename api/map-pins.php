<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/../includes/api-error-handler.php';
setupApiErrorHandler();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function ok($msg, $data=[]) { $j=json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); if(isset($GLOBALS['_ssCacheKey'])&&function_exists('_ssCacheSave'))_ssCacheSave($j); echo $j; exit; }
function err($msg, $code=400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function auth() {
    if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) { $t = verifyJWT($m[1]); if ($t) return $t['user_id']; }
    return null;
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

// GET — List pins (viewport-based, paginated)
if ($method === 'GET') {
    $type = $_GET['type'] ?? '';
    $where = "1=1"; $params = [];
    
    if ($type) { $where .= " AND p.pin_type = ?"; $params[] = $type; }
    
    // Bounding box filter (critical for 100K scale)
    if (isset($_GET['lat1'],$_GET['lng1'],$_GET['lat2'],$_GET['lng2'])) {
        $lat1 = floatval($_GET['lat1']); $lat2 = floatval($_GET['lat2']);
        $lng1 = floatval($_GET['lng1']); $lng2 = floatval($_GET['lng2']);
        $where .= " AND p.lat BETWEEN ? AND ? AND p.lng BETWEEN ? AND ?";
        $params[] = min($lat1,$lat2); $params[] = max($lat1,$lat2);
        $params[] = min($lng1,$lng2); $params[] = max($lng1,$lng2);
    }
    
    // Limit per viewport (prevent loading 100K pins)
    $limit = min(intval($_GET['limit'] ?? 200), 500);
    
    // Cache key based on rounded viewport (coarser cache = more hits)
    $cacheKey = 'pins_' . $type . '_' . round($lat1 ?? 0, 2) . '_' . round($lng1 ?? 0, 2) . '_' . round($lat2 ?? 90, 2) . '_' . round($lng2 ?? 180, 2);
    api_try_cache($cacheKey, 60);
    
    // Select ONLY needed columns (not SELECT *)
    $pins = $db->fetchAll(
        "SELECT p.id, p.user_id, p.lat, p.lng, p.title, p.description, p.pin_type, 
                p.address, p.rating, p.difficulty, p.upvotes, p.downvotes, p.created_at,
                u.fullname as user_name, u.avatar as user_avatar
         FROM map_pins p 
         JOIN users u ON p.user_id = u.id
         WHERE $where 
         ORDER BY p.created_at DESC 
         LIMIT ?", array_merge($params, [$limit])
    );
    // Add user vote status
    $uid = auth();
    if ($uid && $pins) {
        $pinIds = array_map(function($p) { return $p['id']; }, $pins);
        if ($pinIds) {
            $ph = implode(',', array_fill(0, count($pinIds), '?'));
            $votes = $db->fetchAll("SELECT pin_id, vote FROM pin_votes WHERE user_id = ? AND pin_id IN ($ph)", array_merge([$uid], $pinIds));
            $voteMap = [];
            foreach ($votes ?: [] as $v) { $voteMap[$v['pin_id']] = intval($v['vote']); }
            foreach ($pins as &$p) { $p['user_vote'] = $voteMap[$p['id']] ?? 0; }
            unset($p);
        }
    }
    ok('OK', $pins ?: []);
}

// POST — Create pin or Vote
if ($method === 'POST') {
    $uid = auth(); if (!$uid) err('Đăng nhập để thêm ghim', 401);
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    
    // VOTE on pin (dupe-protected via pin_votes)
    if (($input['action'] ?? '') === 'vote') {
        $pinId = intval($input['pin_id'] ?? 0);
        $vote = intval($input['vote'] ?? 0);
        if (!$pinId) err('Missing pin_id');
        $voteVal = $vote > 0 ? 1 : -1;
        
        $existing = $db->fetchOne("SELECT id, vote FROM pin_votes WHERE pin_id = ? AND user_id = ?", [$pinId, $uid]);
        if ($existing) {
            if (intval($existing['vote']) === $voteVal) {
                $db->hardDelete('pin_votes', 'id = ?', [$existing['id']]);
                $col = $voteVal > 0 ? 'upvotes' : 'downvotes';
                $db->query("UPDATE map_pins SET $col = GREATEST(0, $col - 1) WHERE id = ?", [$pinId]);
            } else {
                $db->query("UPDATE pin_votes SET vote = ? WHERE id = ?", [$voteVal, $existing['id']]);
                if ($voteVal > 0) { $db->query("UPDATE map_pins SET upvotes = upvotes + 1, downvotes = GREATEST(0, downvotes - 1) WHERE id = ?", [$pinId]); }
                else { $db->query("UPDATE map_pins SET downvotes = downvotes + 1, upvotes = GREATEST(0, upvotes - 1) WHERE id = ?", [$pinId]); }
            }
        } else {
            $db->insert('pin_votes', ['pin_id' => $pinId, 'user_id' => $uid, 'vote' => $voteVal]);
            $col = $voteVal > 0 ? 'upvotes' : 'downvotes';
            $db->query("UPDATE map_pins SET $col = $col + 1 WHERE id = ?", [$pinId]);
        }
        
        $pin = $db->fetchOne("SELECT upvotes, downvotes FROM map_pins WHERE id = ?", [$pinId]);
        api_cache_flush('pins_');
        ok('Voted', $pin);
    }
    
    // CREATE pin
    $lat = floatval($input['lat'] ?? 0);
    $lng = floatval($input['lng'] ?? 0);
    $title = trim($input['title'] ?? '');
    if (!$lat || !$lng) err('Thiếu tọa độ');
    if (!$title || mb_strlen($title) < 2) err('Tên địa điểm tối thiểu 2 ký tự');
    if (mb_strlen($title) > 200) err('Tên địa điểm tối đa 200 ký tự');
    
    // Rate limit: max 10 pins per user per hour
    $recent = $db->fetchOne("SELECT COUNT(*) as c FROM map_pins WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)", [$uid]);
    if (intval($recent['c'] ?? 0) >= 10) err('Tối đa 10 ghim/giờ. Thử lại sau.');
    
    $pinType = $input['pin_type'] ?? 'note';
    if (!in_array($pinType, ['delivery','warning','note','favorite'])) $pinType = 'note';
    
    $diff = $input['difficulty'] ?? null;
    if ($diff && !in_array($diff, ['easy','medium','hard'])) $diff = null;
    
    $data = [
        'user_id' => $uid,
        'lat' => $lat,
        'lng' => $lng,
        'title' => mb_substr($title, 0, 200),
        'description' => mb_substr(trim($input['description'] ?? ''), 0, 1000),
        'pin_type' => $pinType,
        'address' => mb_substr(trim($input['address'] ?? ''), 0, 500),
        'rating' => max(0, min(5, intval($input['rating'] ?? 0))),
        'created_at' => date('Y-m-d H:i:s')
    ];
    if ($diff) $data['difficulty'] = $diff;
    
    $db->insert('map_pins', $data);
    $newId = $db->getLastInsertId();
    if (!$newId) {
        $row = $db->fetchOne("SELECT MAX(id) as mid FROM map_pins WHERE user_id = ?", [$uid]);
        $newId = $row['mid'] ?? 0;
    }
    
    // Flush cache for this region
    api_cache_flush('pins_');
    
    // Notify followers (async, don't block response)
    try {
        $me = $db->fetchOne("SELECT fullname FROM users WHERE id = ?", [$uid]);
        $mName = $me ? $me['fullname'] : 'Ai đó';
        $followers = $db->fetchAll("SELECT follower_id FROM follows WHERE following_id = ? LIMIT 50", [$uid]);
        if (function_exists('asyncNotify')) {
            foreach ($followers as $f) {
                asyncNotify(intval($f['follower_id']), 'Bản đồ: ' . $mName, mb_substr($title, 0, 50), 'map', '/map.html');
            }
        }
    } catch (Throwable $e) {}
    
    ok('Đã thêm ghim!', ['id' => $newId]);
}

// DELETE
if ($method === 'DELETE') {
    $uid = auth(); if (!$uid) err('Đăng nhập', 401);
    $id = intval($_GET['id'] ?? 0);
    if (!$id) err('Missing id');
    
    // Verify ownership
    $pin = $db->fetchOne("SELECT user_id FROM map_pins WHERE id = ?", [$id]);
    if (!$pin || intval($pin['user_id']) !== intval($uid)) err('Không có quyền xóa');
    
    $db->query("DELETE FROM map_pins WHERE id = ? AND user_id = ?", [$id, $uid]);
    api_cache_flush('pins_');
    ok('Đã xóa');
}

err('Invalid');
