<?php
function getAuthUserId() {
    // System dùng session, không phải JWT
    // Check session user_id
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    // Fallback: decode token từ localStorage (simple base64)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // Try decode as simple JWT
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if ($payload && isset($payload['id'])) {
                // Set session for future requests
                $_SESSION['user_id'] = $payload['id'];
                return $payload['id'];
            }
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}
?>
