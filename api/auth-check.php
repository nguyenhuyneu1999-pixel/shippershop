<?php
function getAuthUserId() {
    // Debug log
    file_put_contents('/home/nhshiw2j/public_html/auth-debug.log', date('Y-m-d H:i:s') . " - Auth check started\n", FILE_APPEND);
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    file_put_contents('/home/nhshiw2j/public_html/auth-debug.log', "Auth header: $authHeader\n", FILE_APPEND);
    
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        file_put_contents('/home/nhshiw2j/public_html/auth-debug.log', "Token found: $token\n", FILE_APPEND);
        
        $userData = verifyCsrfToken($token);
        file_put_contents('/home/nhshiw2j/public_html/auth-debug.log', "Verify result: " . print_r($userData, true) . "\n", FILE_APPEND);
        
        if ($userData && is_array($userData) && isset($userData['id'])) {
            return $userData['id'];
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}
?>
