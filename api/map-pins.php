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

function ok($msg, $data=[]) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $code=400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function auth() {
    if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) { $t = verifyJWT($m[1]); if ($t) return $t['user_id']; }
    return null;
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

// GET - List pins
if ($method === 'GET') {
    api_try_cache('map_pins_' . md5(json_encode($_GET)), 120);
    $type = $_GET['type'] ?? '';
    $where = "1=1"; $params = [];
    if ($type) { $where .= " AND p.pin_type = ?"; $params[] = $type; }
    // Bounding box filter
    if (isset($_GET['lat1'],$_GET['lng1'],$_GET['lat2'],$_GET['lng2'])) {
        $where .= " AND p.lat BETWEEN ? AND ? AND p.lng BETWEEN ? AND ?";
        $params[] = min($_GET['lat1'],$_GET['lat2']); $params[] = max($_GET['lat1'],$_GET['lat2']);
        $params[] = min($_GET['lng1'],$_GET['lng2']); $params[] = max($_GET['lng1'],$_GET['lng2']);
    }
    $pins = $db->fetchAll(
        "SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username
         FROM map_pins p JOIN users u ON p.user_id = u.id
         WHERE $where ORDER BY p.created_at DESC LIMIT 100", $params
    );
    ok('OK', $pins);
}

// POST - Create pin or Vote
if ($method === 'POST') {
    $uid = auth(); if (!$uid) err('Đăng nhập để thêm ghim', 401);
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    
    // VOTE on pin
    if (($input['action'] ?? '') === 'vote') {
        $pinId = intval($input['pin_id'] ?? 0);
        $vote = intval($input['vote'] ?? 0);
        if (!$pinId) err('Missing pin_id');
        if ($vote > 0) {
            $db->query("UPDATE map_pins SET upvotes = upvotes + 1 WHERE id = ?", [$pinId]);
        } else {
            $db->query("UPDATE map_pins SET downvotes = downvotes + 1 WHERE id = ?", [$pinId]);
        }
        $pin = $db->fetchOne("SELECT upvotes, downvotes FROM map_pins WHERE id = ?", [$pinId]);
        ok('Voted', $pin);
    }
    
    // CREATE pin
    $lat = floatval($input['lat'] ?? 0);
    $lng = floatval($input['lng'] ?? 0);
    $title = trim($input['title'] ?? '');
    if (!$lat || !$lng || !$title) err('Thiếu thông tin');
    
    $data = [
        'user_id'=>$uid, 'lat'=>$lat, 'lng'=>$lng, 'title'=>$title,
        'description'=>trim($input['description'] ?? ''),
        'pin_type'=>$input['pin_type'] ?? 'note',
        'address'=>trim($input['address'] ?? ''),
        'rating'=>intval($input['rating'] ?? 0),
        'created_at'=>date('Y-m-d H:i:s')
    ];
    $diff = $input['difficulty'] ?? null;
    if ($diff && in_array($diff, ['easy','medium','hard'])) {
        $data['difficulty'] = $diff;
    }
    
    $id = $db->insert('map_pins', $data);
    // Push: notify followers about new map pin
    try {
        require_once __DIR__.'/../includes/push-helper.php';
        $me = $db->fetchOne("SELECT fullname FROM users WHERE id = ?", [$uid]);
        $mName = $me ? $me['fullname'] : 'Ai đó';
        $followers = $db->fetchAll("SELECT follower_id FROM follows WHERE following_id = ? LIMIT 50", [$uid]);
        foreach ($followers as $f) {
            notifyUser(intval($f['follower_id']), 'Bản đồ: ' . $mName, $title, 'map', '/map.html');
        }
    } catch (Throwable $e) {}
    ok('Đã thêm ghim!', ['id'=>$id]);
}

// DELETE
if ($method === 'DELETE') {
    $uid = auth(); if (!$uid) err('Đăng nhập', 401);
    $id = $_GET['id'] ?? 0;
    $db->query("DELETE FROM map_pins WHERE id = ? AND user_id = ?", [$id, $uid]);
    ok('Đã xóa');
}

err('Invalid');