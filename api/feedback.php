<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$d = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = getOptionalAuthUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'feedback';
    $message = trim($input['message'] ?? '');
    $email = trim($input['email'] ?? '');
    
    if (strlen($message) < 10) { error('Nội dung tối thiểu 10 ký tự'); }
    
    $d->query("INSERT INTO settings (`key`, value) VALUES (?, ?)", [
        'feedback_' . time(),
        json_encode(['user_id' => $uid, 'type' => $type, 'message' => $message, 'email' => $email, 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'at' => date('Y-m-d H:i:s')])
    ]);
    
    success('Cảm ơn bạn! Phản hồi đã được gửi.');
}

echo json_encode(['success' => false, 'message' => 'POST only']);
