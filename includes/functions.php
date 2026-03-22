<?php
/**
 * ============================================
 * HELPER FUNCTIONS
 * ============================================
 */

defined('APP_ACCESS') or define('APP_ACCESS', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ============================================
// INPUT VALIDATION & SANITIZATION
// ============================================

/**
 * Sanitize string input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Vietnamese format)
 */
function validatePhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check if it matches Vietnamese phone format
    return preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Mật khẩu phải có ít nhất " . PASSWORD_MIN_LENGTH . " ký tự";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 chữ hoa";
    }
    
    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 1 số";
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Validate required fields
 */
function validateRequired($fields, $data) {
    $errors = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = "Trường này là bắt buộc";
        }
    }
    
    return $errors;
}

// ============================================
// FORMATTING FUNCTIONS
// ============================================

/**
 * Format currency (VND)
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } else {
        return formatDate($timestamp);
    }
}

/**
 * Slugify string (for URLs)
 */
function slugify($text) {
    // Convert Vietnamese characters
    $vietnamese = [
        'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
        'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
        'ì','í','ị','ỉ','ĩ',
        'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
        'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
        'ỳ','ý','ỵ','ỷ','ỹ',
        'đ',
        'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
        'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
        'Ì','Í','Ị','Ỉ','Ĩ',
        'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
        'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
        'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
        'Đ'
    ];
    
    $ascii = [
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
        'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
        'E','E','E','E','E','E','E','E','E','E','E',
        'I','I','I','I','I',
        'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
        'U','U','U','U','U','U','U','U','U','U','U',
        'Y','Y','Y','Y','Y',
        'D'
    ];
    
    $text = str_replace($vietnamese, $ascii, $text);
    $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    
    return $text;
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

/**
 * Upload file
 */
function uploadFile($file, $folder = 'general', $allowedTypes = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'Upload error: ' . $file['error']
        ];
    }
    
    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return [
            'success' => false,
            'message' => 'File quá lớn. Tối đa ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB'
        ];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Loại file không được phép'
        ];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Create folder if not exists
    $uploadPath = UPLOAD_DIR . $folder . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $fullPath = $uploadPath . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $folder . '/' . $filename,
            'url' => SITE_URL . '/uploads/' . $folder . '/' . $filename
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Không thể upload file'
        ];
    }
}

/**
 * Delete file
 */
function deleteFile($path) {
    $fullPath = UPLOAD_DIR . $path;
    
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    
    return false;
}

// ============================================
// JSON RESPONSE FUNCTIONS
// ============================================

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    // Auto-save pending cache (from api_try_cache MISS)
    if (isset($GLOBALS['_ssPendingCacheKey']) && $statusCode === 200) {
        try { api_cache_set($GLOBALS['_ssPendingCacheKey'], $json, $GLOBALS['_ssPendingCacheTTL'] ?? 30); } catch (Throwable $e) {}
        unset($GLOBALS['_ssPendingCacheKey'], $GLOBALS['_ssPendingCacheTTL']);
    }
    echo $json;
    exit;
}

/**
 * Send success response
 */
function success($message = 'Success', $data = null) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    jsonResponse($response);
}

/**
 * Send error response
 */
function error($message = 'Error', $statusCode = 400, $errors = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    jsonResponse($response, $statusCode);
}

// ============================================
// PAGINATION FUNCTIONS
// ============================================

/**
 * Calculate pagination
 */
function paginate($totalItems, $currentPage = 1, $itemsPerPage = null) {
    if ($itemsPerPage === null) {
        $itemsPerPage = API_DEFAULT_LIMIT;
    }
    
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'current_page' => $currentPage,
        'per_page' => $itemsPerPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_more' => $currentPage < $totalPages
    ];
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Verify JWT token with HMAC-SHA256 signature verification
 * Returns payload array on success, null on failure
 * CRITICAL: Never just base64_decode - always verify signature!
 * Note: Also defined in config.php - this is fallback only
 */
if (!function_exists('verifyJWT')) {
function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];

    // Verify HMAC-SHA256 signature against JWT_SECRET
    $validSig = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    $validSig = rtrim(strtr($validSig, '+/', '-_'), '=');
    $testSig = rtrim(strtr($signature, '+/', '-_'), '=');

    if (!hash_equals($validSig, $testSig)) {
        return null; // Signature mismatch - REJECT
    }

    // Signature valid - decode payload (handle URL-safe base64)
    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    if (!$data || !isset($data['user_id'])) return null;

    // Check expiry if present
    if (isset($data['exp']) && $data['exp'] < time()) return null;

    return $data;
}
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting (simple session-based implementation)
 * Note: wallet-api.php has its own DB-based rateLimit() which takes priority
 */
if (!function_exists('rateLimit')) {
function rateLimit($identifier, $maxRequests = null, $timeWindow = 3600) {
    if ($maxRequests === null) {
        $maxRequests = API_RATE_LIMIT;
    }
    
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'reset_time' => time() + $timeWindow
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() > $data['reset_time']) {
        $_SESSION[$key] = [
            'count' => 1,
            'reset_time' => time() + $timeWindow
        ];
        return true;
    }
    
    // Check limit
    if ($data['count'] >= $maxRequests) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    return true;
}
}

// ============================================
// EMAIL FUNCTIONS (for future use)
// ============================================

/**
 * Send email
 */
function sendEmail($to, $subject, $body, $from = null) {
    if ($from === null) {
        $from = SITE_EMAIL;
    }
    
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'DH' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Calculate shipping fee
 */
function calculateShippingFee($subtotal) {
    return $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
}

/**
 * Get client IP
 */
function getClientIP() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($keys as $key) {
        if (isset($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    
    return 'Unknown';
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Get JSON input
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Redirect
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Debug helper
 */
function dd(...$vars) {
    if (!DEBUG_MODE) return;
    
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

// ============================================
// FEATURE GATING - Free vs Plus limits
// ============================================

/**
 * Get user's current subscription plan and limits
 * Returns: ['plan'=>'free'|'plus', 'limits'=>[...], 'is_plus'=>bool]
 */
function getUserPlan($userId) {
    if (!$userId) return ['plan' => 'free', 'is_plus' => false, 'badge' => null];
    
    $sub = db()->fetchOne(
        "SELECT us.plan_id, sp.slug, sp.badge, sp.max_posts_per_day 
         FROM user_subscriptions us 
         JOIN subscription_plans sp ON us.plan_id = sp.id 
         WHERE us.user_id = ? AND us.`status` = 'active' AND us.expires_at > NOW() 
         ORDER BY us.expires_at DESC LIMIT 1", 
        [$userId]
    );
    
    $slug = $sub ? $sub['slug'] : 'free';
    $isPlus = ($slug === 'plus');
    
    return [
        'plan' => $slug,
        'is_plus' => $isPlus,
        'badge' => $sub ? $sub['badge'] : null,
        'limits' => [
            'posts_per_day' => $isPlus ? 9999 : 10,
            'messages_per_month' => $isPlus ? 999999 : 500,
            'groups_max' => $isPlus ? 999 : 10,
            'marketplace_max' => $isPlus ? 20 : 3,
            'call_minutes_per_day' => $isPlus ? 9999 : 10,
        ]
    ];
}

/**
 * Check a specific limit. Returns null if OK, or error message string if exceeded.
 * Usage: $err = checkLimit($userId, 'posts_per_day'); if($err) error($err);
 */
function checkLimit($userId, $limitKey) {
    $plan = getUserPlan($userId);
    $limit = $plan['limits'][$limitKey] ?? 9999;
    $d = db();
    
    switch ($limitKey) {
        case 'posts_per_day':
            $count = $d->fetchOne(
                "SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND DATE(created_at) = CURDATE() AND `status` = 'active'", 
                [$userId]
            )['c'];
            if ($count >= $limit) {
                return $plan['is_plus'] 
                    ? null  // Plus users shouldn't hit this
                    : "Bạn đã đăng $count/$limit bài hôm nay. Nâng cấp Shipper Plus để đăng không giới hạn!";
            }
            return null;
            
        case 'messages_per_month':
            $count = $d->fetchOne(
                "SELECT COUNT(*) as c FROM messages WHERE sender_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", 
                [$userId]
            )['c'];
            if ($count >= $limit) {
                return $plan['is_plus']
                    ? null
                    : "Bạn đã gửi $count/$limit tin nhắn tháng này. Nâng cấp Shipper Plus để nhắn không giới hạn!";
            }
            return null;
            
        case 'groups_max':
            $count = $d->fetchOne(
                "SELECT COUNT(*) as c FROM group_members WHERE user_id = ?", 
                [$userId]
            )['c'];
            if ($count >= $limit) {
                return $plan['is_plus']
                    ? null
                    : "Bạn đã tham gia $count/$limit nhóm. Nâng cấp Shipper Plus để tham gia không giới hạn!";
            }
            return null;
            
        case 'marketplace_max':
            $count = $d->fetchOne(
                "SELECT COUNT(*) as c FROM marketplace_listings WHERE user_id = ? AND `status` = 'active'", 
                [$userId]
            )['c'];
            if ($count >= $limit) {
                return $plan['is_plus']
                    ? null
                    : "Bạn đã đăng $count/$limit sản phẩm. Nâng cấp Shipper Plus để đăng tối đa 20!";
            }
            return null;
            
        default:
            return null;
    }
}

/**
 * Get usage stats for a user (for frontend display)
 */
function getUserUsage($userId) {
    $d = db();
    return [
        'posts_today' => intval($d->fetchOne(
            "SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND DATE(created_at) = CURDATE() AND `status` = 'active'", [$userId]
        )['c']),
        'messages_month' => intval($d->fetchOne(
            "SELECT COUNT(*) as c FROM messages WHERE sender_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", [$userId]
        )['c']),
        'groups_joined' => intval($d->fetchOne(
            "SELECT COUNT(*) as c FROM group_members WHERE user_id = ?", [$userId]
        )['c']),
        'marketplace_active' => intval($d->fetchOne(
            "SELECT COUNT(*) as c FROM marketplace_listings WHERE user_id = ? AND `status` = 'active'", [$userId]
        )['c']),
    ];
}
