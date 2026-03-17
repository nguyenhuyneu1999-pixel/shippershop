<?php
/**
 * AUTHENTICATION API - FIXED VERSION
 * Endpoints:
 * POST ?action=register  - Đăng ký
 * POST ?action=login     - Đăng nhập
 * POST ?action=logout    - Đăng xuất
 * GET  ?action=me        - Thông tin user hiện tại
 * POST ?action=update_profile  - Cập nhật profile
 * POST ?action=change_password - Đổi mật khẩu
 */

define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function apiSuccess($message, $data = []) {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function apiError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function getInput() {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if ($json) return $json;
    }
    return array_merge($_POST, $_GET);
}

// Get database connection
try {
    $db = db();
} catch (Exception $e) {
    apiError('Lỗi kết nối database: ' . $e->getMessage(), 500);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================
// ĐĂNG KÝ
// ============================================
if ($action === 'register') {
    $input = getInput();

    $fullname = trim($input['fullname'] ?? '');
    $email    = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $phone    = trim($input['phone'] ?? '');

    if (empty($fullname) || empty($email) || empty($password)) {
        apiError('Vui lòng điền đầy đủ họ tên, email và mật khẩu');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        apiError('Email không hợp lệ');
    }

    if (strlen($password) < 6) {
        apiError('Mật khẩu tối thiểu 6 ký tự');
    }

    // Kiểm tra email tồn tại
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        apiError('Email này đã được đăng ký');
    }

    // Tạo user
    $hashedPw = password_hash($password, PASSWORD_BCRYPT);

    try {
        $userId = $db->insert('users', [
            'fullname'   => $fullname,
            'email'      => $email,
            'password'   => $hashedPw,
            'phone'      => $phone,
            'role'       => 'user',
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Tạo ví tiền
        try {
            $db->insert('wallets', ['user_id' => $userId, 'balance' => 0]);
        } catch (Exception $e) {}

        // Tạo session
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'user';

        $token = generateJWT($userId, $email, 'user');

        apiSuccess('Đăng ký thành công! Chào mừng ' . $fullname, [
            'user'  => ['id' => $userId, 'email' => $email, 'fullname' => $fullname, 'role' => 'user'],
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
    $input    = getInput();
    $email    = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        apiError('Vui lòng nhập email và mật khẩu');
    }

    // Lấy user từ DB
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE email = ? AND status = 'active'",
        [$email]
    );

    if (!$user) {
        apiError('Email hoặc mật khẩu không đúng', 401);
    }

    if (!password_verify($password, $user['password'])) {
        apiError('Email hoặc mật khẩu không đúng', 401);
    }

    // Tạo session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    // Cập nhật last_login
    try {
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    } catch (Exception $e) {}

    $token = generateJWT($user['id'], $user['email'], $user['role']);

    // Lấy số dư ví
    $wallet = $db->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$user['id']]);

    apiSuccess('Đăng nhập thành công!', [
        'user' => [
            'id'       => $user['id'],
            'email'    => $user['email'],
            'fullname' => $user['fullname'],
            'phone'    => $user['phone'],
            'avatar'   => $user['avatar'],
            'role'     => $user['role'],
            'wallet_balance' => $wallet['balance'] ?? 0
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
// THÔNG TIN USER HIỆN TẠI
// ============================================
if ($action === 'me') {
    if (!isset($_SESSION['user_id'])) {
        // Kiểm tra JWT token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            $tokenData = verifyJWT($matches[1]);
            if ($tokenData) {
                $_SESSION['user_id']    = $tokenData['user_id'];
                $_SESSION['user_email'] = $tokenData['email'];
                $_SESSION['user_role']  = $tokenData['role'];
            }
        }
        if (!isset($_SESSION['user_id'])) {
            apiError('Chưa đăng nhập', 401);
        }
    }

    $userId = $_SESSION['user_id'];
    $user = $db->fetchOne(
        "SELECT id, email, fullname, phone, avatar, bio, address, role, created_at FROM users WHERE id = ? AND status = 'active'",
        [$userId]
    );

    if (!$user) {
        apiError('Tài khoản không tồn tại', 404);
    }

    $wallet = $db->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$userId]);
    $user['wallet_balance'] = $wallet['balance'] ?? 0;

    $user['stats'] = [
        'total_orders' => $db->count('orders', 'user_id = ?', [$userId]),
        'cart_items'   => $db->count('cart',   'user_id = ?', [$userId]),
        'total_posts'  => $db->count('posts',  "user_id = ? AND status = 'active'", [$userId]),
    ];

    apiSuccess('OK', $user);
}

// ============================================
// CẬP NHẬT PROFILE
// ============================================
if ($action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) {
        apiError('Chưa đăng nhập', 401);
    }

    $userId = $_SESSION['user_id'];
    $input  = getInput();

    $update = [];
    foreach (['fullname', 'phone', 'bio', 'address'] as $field) {
        if (isset($input[$field])) {
            $update[$field] = trim($input[$field]);
        }
    }

    if (empty($update)) {
        apiError('Không có dữ liệu cập nhật');
    }

    $db->update('users', $update, 'id = ?', [$userId]);
    apiSuccess('Cập nhật thành công', $update);
}

// ============================================
// ĐỔI MẬT KHẨU
// ============================================
if ($action === 'change_password') {
    if (!isset($_SESSION['user_id'])) {
        apiError('Chưa đăng nhập', 401);
    }

    $userId   = $_SESSION['user_id'];
    $input    = getInput();
    $oldPass  = $input['old_password'] ?? '';
    $newPass  = $input['new_password'] ?? '';

    if (empty($oldPass) || empty($newPass)) {
        apiError('Vui lòng nhập đủ mật khẩu cũ và mới');
    }

    if (strlen($newPass) < 6) {
        apiError('Mật khẩu mới tối thiểu 6 ký tự');
    }

    $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);

    if (!password_verify($oldPass, $user['password'])) {
        apiError('Mật khẩu cũ không đúng');
    }

    $db->update('users', ['password' => password_hash($newPass, PASSWORD_BCRYPT)], 'id = ?', [$userId]);
    apiSuccess('Đổi mật khẩu thành công');
}

// Action không hợp lệ
apiError('Action không hợp lệ: ' . $action, 400);
