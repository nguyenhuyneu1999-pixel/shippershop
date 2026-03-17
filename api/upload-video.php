<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Get token from header
$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
    $_SERVER['HTTP_AUTHORIZATION'] = $auth;
}
$userId = getAuthUserId();
if (!$userId) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['video'])) {
    echo json_encode(["success"=>false,"message"=>"No video file"]); exit;
}

$file = $_FILES['video'];
$maxSize = 50 * 1024 * 1024; // 50MB
if ($file['size'] > $maxSize) {
    echo json_encode(["success"=>false,"message"=>"Video quá lớn (tối đa 50MB)"]); exit;
}

$allowed = ['video/mp4','video/quicktime','video/webm','video/avi','video/mov'];
$ftype = $file['type'];
if (!in_array($ftype, $allowed) && strpos($ftype, 'video/') !== 0) {
    echo json_encode(["success"=>false,"message"=>"Định dạng video không hỗ trợ"]); exit;
}

$dir = __DIR__ . '/../uploads/videos/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp4';
$name = 'vid_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
    echo json_encode(["success"=>true,"url"=>"/uploads/videos/" . $name], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success"=>false,"message"=>"Upload thất bại"]);
}
