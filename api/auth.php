<?php
session_start();
/**
 * AUTHENTICATION API
 * POST ?action=register       - Đăng ký (username)
 * POST ?action=login          - Đăng nhập (username hoặc email)
 * POST ?action=logout         - Đăng xuất
 * GET  ?action=me             - Thông tin user
 * POST ?action=update_profile - Cập nhật profile
 * POST ?action=change_password- Đổi mật khẩu
 * POST ?action=upload_avatar  - Upload ảnh đại diện
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'https://shippershop.vn' || $origin === 'http://shippershop.vn' || $origin === '') {
    header('Access-Control-Allow-Origin: ' . ($origin ?: 'https://shippershop.vn'));
} else {
    header('Access-Control-Allow-Origin: https://shippershop.vn');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function apiSuccess($message, $data = []) {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}
function apiError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
function getInput() {
    $raw = file_get_contents('php://input');
    if ($raw) { $json = json_decode($raw, true); if ($json) return $json; }
    return array_merge($_POST, $_GET);
}
function getAuthUser() {
    if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $td = verifyJWT($m[1]);
        if ($td) { $_SESSION['user_id'] = $td['user_id']; return $td['user_id']; }
    }
    return null;
}

try { $db = db(); } catch (Exception $e) { apiError('Lỗi kết nối database', 500); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================
// ĐĂNG KÝ
// ============================================
if ($action === 'register') {
    // Rate limit: max 3 registrations per IP per hour
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = explode(',', $ip)[0];
    $window = date('Y-m-d H:i:s', time() - 3600);
    $regCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND success = 1 AND created_at > ?", [$ip, $window]);
    if (intval($regCount['cnt'] ?? 0) >= 3) {
        apiError('Quá nhiều tài khoản được tạo. Vui lòng thử lại sau.', 429);
    }

    $input = getInput();
    $fullname = trim($input['fullname'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $shipping_company = trim($input['shipping_company'] ?? '');

    if (empty($fullname) || empty($username) || empty($password)) {
        apiError('Vui lòng điền đầy đủ họ tên, tên đăng nhập và mật khẩu');
    }
    if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        apiError('Tên đăng nhập phải 3-30 ký tự, chỉ chữ thường, số và _');
    }
    if (strlen($password) < 8) {
        apiError('Mật khẩu tối thiểu 8 ký tự');
    }
    $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        apiError('Tên đăng nhập đã được sử dụng');
    }

    $hashedPw = password_hash($password, PASSWORD_BCRYPT);
    try {
        $userId = $db->insert('users', [
            'fullname'         => $fullname,
            'username'         => $username,
            'email'            => $username . '@shippershop.local',
            'password'         => $hashedPw,
            'phone'            => '',
            'shipping_company' => $shipping_company,
            'role'             => 'user',
            'status'           => 'active',
            'created_at'       => date('Y-m-d H:i:s')
        ]);

        try { $db->insert('wallets', ['user_id' => $userId, 'balance' => 0]); } catch (Exception $e) {}
        try { $db->query("INSERT INTO login_attempts (ip, user_id, email, success, created_at) VALUES (?, ?, ?, 1, NOW())", [$ip, $userId, $username]); } catch (Throwable $e) {}

        $_SESSION['user_id'] = $userId;
        $token = generateJWT($userId, $username . '@shippershop.local', 'user');

        apiSuccess('Đăng ký thành công! Chào mừng ' . $fullname, [
            'user'  => [
                'id' => $userId, 'username' => $username, 'fullname' => $fullname,
                'shipping_company' => $shipping_company, 'role' => 'user', 'avatar' => null
            ],
            'token' => $token
        ]);
    } catch (Exception $e) {
        apiError('Đăng ký thất bại: ' . $e->getMessage(), 500);
    }
}

// ============================================
// ĐĂNG NHẬP
// ============================================
if ($action === 'login') {
    $input = getInput();
    $loginId  = trim($input['username'] ?? $input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($loginId) || empty($password)) {
        apiError('Vui lòng nhập tên đăng nhập và mật khẩu');
    }

    // Brute force protection: 5 failed attempts in 15 min = lock (by IP OR by email)
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = explode(',', $ip)[0];
    $window = date('Y-m-d H:i:s', time() - 900);
    $ipAttempts = $db->fetchOne("SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > ?", [$ip, $window]);
    $emailAttempts = $db->fetchOne("SELECT COUNT(*) as cnt FROM login_attempts WHERE email = ? AND success = 0 AND created_at > ?", [$loginId, $window]);
    if (intval($ipAttempts['cnt'] ?? 0) >= 5 || intval($emailAttempts['cnt'] ?? 0) >= 5) {
        apiError('Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau 15 phút.', 429);
    }

    // Tìm user bằng username HOẶC email (tương thích ngược)
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
        [$loginId, $loginId]
    );

    if (!$user) {
        try { $db->query("INSERT INTO login_attempts (ip, email, success, created_at) VALUES (?, ?, 0, NOW())", [$ip, $loginId]); } catch (Throwable $e) {}
        apiError('Tên đăng nhập hoặc mật khẩu không đúng', 401);
    }
    if (!password_verify($password, $user['password'])) {
        try { $db->query("INSERT INTO login_attempts (ip, user_id, email, success, created_at) VALUES (?, ?, ?, 0, NOW())", [$ip, $user['id'], $loginId]); } catch (Throwable $e) {}
        apiError('Tên đăng nhập hoặc mật khẩu không đúng', 401);
    }

    // Login success - clear attempts
    try { $db->query("DELETE FROM login_attempts WHERE ip = ? AND success = 0", [$ip]); } catch (Throwable $e) {}

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    try { $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]); } catch (Exception $e) {}

    $token = generateJWT($user['id'], $user['email'], $user['role']);
    $wallet = $db->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$user['id']]);

    apiSuccess('Đăng nhập thành công!', [
        'user' => [
            'id'               => $user['id'],
            'username'         => $user['username'] ?? '',
            'email'            => $user['email'],
            'fullname'         => $user['fullname'],
            'phone'            => $user['phone'],
            'avatar'           => $user['avatar'],
            'shipping_company' => $user['shipping_company'] ?? '',
            'role'             => $user['role'],
            'wallet_balance'   => $wallet['balance'] ?? 0
        ],
        'token' => $token
    ]);
}

// ============================================
// ĐĂNG XUẤT
// ============================================
if ($action === 'logout') {
    session_destroy();
    apiSuccess('Đăng xuất thành công');
}

// ============================================
// THÔNG TIN USER
// ============================================
if ($action === 'me') {
    $userId = getAuthUser();
    if (!$userId) apiError('Chưa đăng nhập', 401);

    $user = $db->fetchOne(
        "SELECT id, username, email, fullname, phone, avatar, bio, address, role, shipping_company, created_at FROM users WHERE id = ? AND status = 'active'",
        [$userId]
    );
    if (!$user) apiError('Tài khoản không tồn tại', 404);

    $wallet = $db->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$userId]);
    $user['wallet_balance'] = $wallet['balance'] ?? 0;
    $user['stats'] = [
        'total_orders' => $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE user_id = ?", [$userId])['c'],
        'cart_items'   => $db->fetchOne("SELECT COUNT(*) as c FROM cart WHERE user_id = ?", [$userId])['c'],
        'total_posts'  => $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND status = 'active'", [$userId])['c'],
    ];
    apiSuccess('OK', $user);
}

// ============================================
// CẬP NHẬT PROFILE
// ============================================
if ($action === 'update_profile') {
    $userId = getAuthUser();
    if (!$userId) apiError('Chưa đăng nhập', 401);

    $input = getInput();
    $update = [];
    foreach (['fullname', 'username', 'phone', 'bio', 'address', 'shipping_company'] as $field) {
        if (isset($input[$field])) $update[$field] = trim($input[$field]);
    }
    if (empty($update)) apiError('Không có dữ liệu cập nhật');

    $db->update('users', $update, 'id = ?', [$userId]);
    apiSuccess('Cập nhật thành công', $update);
}

// ============================================
// ĐỔI MẬT KHẨU
// ============================================
if ($action === 'change_password') {
    $userId = getAuthUser();
    if (!$userId) apiError('Chưa đăng nhập', 401);

    $input   = getInput();
    $oldPass = $input['old_password'] ?? '';
    $newPass = $input['new_password'] ?? '';

    if (empty($oldPass) || empty($newPass)) apiError('Vui lòng nhập đủ mật khẩu cũ và mới');
    if (strlen($newPass) < 6) apiError('Mật khẩu mới tối thiểu 6 ký tự');

    $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
    if (!password_verify($oldPass, $user['password'])) apiError('Mật khẩu cũ không đúng');

    $db->update('users', ['password' => password_hash($newPass, PASSWORD_BCRYPT)], 'id = ?', [$userId]);
    apiSuccess('Đổi mật khẩu thành công');
}

// ============================================
// UPLOAD AVATAR
// ============================================
if ($action === 'upload_avatar') {
    $userId = getAuthUser();
    if (!$userId) apiError('Chưa đăng nhập', 401);

    if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        apiError('Vui lòng chọn ảnh. Error code: ' . ($_FILES['avatar']['error'] ?? 'no file'));
    }
    $file = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowed)) apiError('Chỉ chấp nhận JPG, PNG, GIF, WebP. Got: ' . $mimeType);
    if ($file['size'] > 5 * 1024 * 1024) apiError('Ảnh tối đa 5MB');

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) $ext = 'jpg';
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $avatarUrl = '/uploads/avatars/' . $filename;
        // Delete old
        $old = $db->fetchOne("SELECT avatar FROM users WHERE id = ?", [$userId]);
        if (!empty($old['avatar']) && strpos($old['avatar'], '/avatars/') !== false) {
            @unlink(__DIR__ . '/..' . $old['avatar']);
        }
        $db->update('users', ['avatar' => $avatarUrl], 'id = ?', [$userId]);
        apiSuccess('Cập nhật ảnh đại diện thành công', ['avatar' => $avatarUrl]);
    } else {
        apiError('Lỗi di chuyển file upload');
    }
}

// ============================================
// GOOGLE LOGIN (giữ lại cho tương lai)
// ============================================
if ($action === 'google_login') {
    apiError('Google login chưa được cấu hình', 501);
}
if ($action === 'facebook_login') {
    apiError('Facebook login chưa được cấu hình', 501);
}

apiError('Action không hợp lệ: ' . $action, 400);
if ($action === 'search_users') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode(['success'=>true,'data'=>[]]); exit; }
    $db = db();
    $users = $db->fetchAll("SELECT id, fullname, username, avatar FROM users WHERE (fullname LIKE ? OR username LIKE ?) LIMIT 10", ["%$q%", "%$q%"]);
    echo json_encode(['success'=>true,'data'=>$users], JSON_UNESCAPED_UNICODE); exit;
}
