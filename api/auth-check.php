<?php
function getAuthUserId() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $userData = verifyCsrfToken($token); // Dùng hàm có sẵn
        
        if ($userData && isset($userData['id'])) {
            return $userData['id'];
        }
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}
?>
