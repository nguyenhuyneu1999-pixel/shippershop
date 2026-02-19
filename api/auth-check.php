<?php
function getAuthUserId() {
    file_put_contents("/home/nhshiw2j/public_html/auth-debug.log", date("Y-m-d H:i:s") . " - Function called\n", FILE_APPEND);
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    file_put_contents("/home/nhshiw2j/public_html/auth-debug.log", "Headers: " . print_r($headers, true) . "\n", FILE_APPEND);
    }
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if ($payload && isset($payload['id'])) {
                $_SESSION['user_id'] = $payload['id'];
                return $payload['id'];
            }
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}
