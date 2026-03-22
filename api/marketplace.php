<?php
define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/../includes/api-error-handler.php';
setupApiErrorHandler();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function mSuccess($msg, $data = []) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function mError($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function mAuth() {
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
function mInput() {
    $raw = file_get_contents('php://input');
    if ($raw) { $j = json_decode($raw, true); if ($j) return $j; }
    return array_merge($_POST, $_GET);
}

try { $db = db(); } catch (Exception $e) { mError('DB error', 500); }
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// ==========================================
// GET - List / Detail
// ==========================================
if ($method === 'GET' && empty($action)) {
    if ($id) {
        // Single listing
        $item = $db->fetchOne(
            "SELECT l.*, u.fullname as seller_name, u.avatar as seller_avatar, u.username as seller_username, u.shipping_company
             FROM marketplace_listings l JOIN users u ON l.user_id = u.id
             WHERE l.id = ? AND l.status IN ('active','sold')", [$id]
        );
        if (!$item) mError('Không tìm thấy tin đăng', 404);
        $db->query("UPDATE marketplace_listings SET views_count = views_count + 1 WHERE id = ?", [$id]);
        mSuccess('OK', $item);
    }
    // List
    $cat = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $userId = $_GET['user_id'] ?? '';

    $where = ["l.status = 'active'"];
    $params = [];
    if ($cat) { $where[] = "l.category = ?"; $params[] = $cat; }
    if ($search) { $where[] = "(l.title LIKE ? OR l.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($userId) { $where[] = "l.user_id = ?"; $params[] = $userId; }

    $whereStr = implode(' AND ', $where);
    $total = $db->fetchOne("SELECT COUNT(*) as cnt FROM marketplace_listings l WHERE $whereStr", $params)['cnt'];
    $items = $db->fetchAll(
        "SELECT l.*, u.fullname as seller_name, u.avatar as seller_avatar, u.username as seller_username
         FROM marketplace_listings l JOIN users u ON l.user_id = u.id
         WHERE $whereStr ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset", $params
    );
    mSuccess('OK', ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
}

// ==========================================
// POST - Create listing / Upload images
// ==========================================
if ($method === 'POST') {
    $uid = mAuth();
    if (!$uid) mError('Vui lòng đăng nhập', 401);

    // Upload images
    if ($action === 'upload') {
        $uploadDir = __DIR__ . '/../uploads/marketplace/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $urls = [];
        $videoUrl = null;
        
        // Handle image uploads
        if (!empty($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < min($count, 10); $i++) {
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $origName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $err = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                if ($err !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                if ($size > 10 * 1024 * 1024) continue; // 10MB max
                $fname = 'mk_' . $uid . '_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $fname)) {
                    $urls[] = '/uploads/marketplace/' . $fname;
                }
            }
        }
        
        // Handle video upload
        if (!empty($_FILES['video'])) {
            $vid = $_FILES['video'];
            if ($vid['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($vid['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['mp4','mov','webm','avi']) && $vid['size'] <= 50 * 1024 * 1024) {
                    $vname = 'mkv_' . $uid . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($vid['tmp_name'], $uploadDir . $vname)) {
                        $videoUrl = '/uploads/marketplace/' . $vname;
                    }
                }
            }
        }
        
        if (empty($urls) && !$videoUrl) mError('Không upload được file nào');
        mSuccess('OK', ['urls' => $urls, 'video_url' => $videoUrl]);
    }

    // Create listing
    // Feature gate: check max active listings for free users
    $limitErr = checkLimit($uid, 'marketplace_max');
    if ($limitErr) { mError($limitErr); }
    
    $input = mInput();
    $title = trim($input['title'] ?? '');
    $desc = trim($input['description'] ?? '');
    $price = intval($input['price'] ?? 0);
    $cat = trim($input['category'] ?? 'khac');
    $cond = trim($input['condition_type'] ?? 'good');
    $images = $input['images'] ?? '[]';
    $location = trim($input['location'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if (empty($title)) mError('Vui lòng nhập tiêu đề');
    if ($price < 0) mError('Giá không hợp lệ');
    if (is_array($images)) $images = json_encode($images);

    $db->query("INSERT INTO marketplace_listings (user_id,title,description,description_images,showcase_images,price,category,condition_type,images,video_url,location,phone,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
        [$uid, $title, $desc, $input['description_images'] ?? null, $input['showcase_images'] ?? null, $price, $cat, $cond, $images, $input['video_url'] ?? null, $location, $phone, 'active']);
    $listingId = $db->getLastInsertId();
    mSuccess('Đăng tin thành công!', ['id' => $listingId]);
}

// ==========================================
// PUT - Update listing
// ==========================================
if ($method === 'PUT') {
    $uid = mAuth();
    if (!$uid) mError('Chưa đăng nhập', 401);
    $input = mInput();
    $listingId = $input['id'] ?? $id;
    $listing = $db->fetchOne("SELECT * FROM marketplace_listings WHERE id = ? AND user_id = ?", [$listingId, $uid]);
    if (!$listing) mError('Không tìm thấy hoặc không có quyền', 404);
    $update = [];
    foreach (['title','description','price','category','condition_type','images','location','phone','status'] as $f) {
        if (isset($input[$f])) $update[$f] = $input[$f];
    }
    if (isset($update['images']) && is_array($update['images'])) $update['images'] = json_encode($update['images']);
    if (!empty($update)) $db->update('marketplace_listings', $update, 'id = ?', [$listingId]);
    mSuccess('Cập nhật thành công');
}

// ==========================================
// DELETE
// ==========================================
if ($method === 'DELETE') {
    $uid = mAuth();
    if (!$uid) mError('Chưa đăng nhập', 401);
    $listingId = $_GET['id'] ?? null;
    if (!$listingId) mError('Thiếu ID');
    $listing = $db->fetchOne("SELECT * FROM marketplace_listings WHERE id = ? AND user_id = ?", [$listingId, $uid]);
    if (!$listing) mError('Không tìm thấy', 404);
    $db->update('marketplace_listings', ['status' => 'deleted'], 'id = ?', [$listingId]);
    mSuccess('Đã xóa');
}

mError('Invalid request', 400);