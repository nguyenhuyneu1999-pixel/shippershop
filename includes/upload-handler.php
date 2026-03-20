<?php
/**
 * ShipperShop Upload Handler — Secure file upload with resize + validation
 * Usage: $result = handle_upload($_FILES['image'], 'posts', ['user_id'=>5, 'resize_max'=>1920]);
 */

/**
 * Handle file upload securely
 * @param array $file $_FILES element
 * @param string $folder subfolder in uploads/ (posts, avatars, covers, messages, traffic)
 * @param array $opts options
 * @return array ['success'=>bool, 'url'=>string, 'error'=>string]
 */
function handle_upload($file, $folder, $opts = []) {
    $maxSize = isset($opts['max_size']) ? $opts['max_size'] : 5 * 1024 * 1024; // 5MB default
    $allowedTypes = isset($opts['allowed_types']) ? $opts['allowed_types'] : ['image/jpeg','image/png','image/webp','image/gif'];
    $resizeMax = isset($opts['resize_max']) ? $opts['resize_max'] : 1920;
    $userId = isset($opts['user_id']) ? $opts['user_id'] : 0;
    
    // Basic validation
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errMsg = 'Upload thất bại';
        if (isset($file['error'])) {
            $errCodes = [
                UPLOAD_ERR_INI_SIZE => 'File quá lớn (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File quá lớn',
                UPLOAD_ERR_PARTIAL => 'Upload không hoàn tất',
                UPLOAD_ERR_NO_FILE => 'Không có file',
            ];
            $errMsg = isset($errCodes[$file['error']]) ? $errCodes[$file['error']] : $errMsg;
        }
        return ['success' => false, 'url' => null, 'error' => $errMsg];
    }
    
    // Size check
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'url' => null, 'error' => 'File tối đa ' . round($maxSize/1024/1024) . 'MB'];
    }
    
    // MIME check via finfo (secure, not trusting client)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedTypes)) {
        return ['success' => false, 'url' => null, 'error' => 'Loại file không hợp lệ: ' . $mime];
    }
    
    // Generate safe filename
    $extMap = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif',
               'video/mp4'=>'mp4', 'video/quicktime'=>'mov', 'application/pdf'=>'pdf'];
    $ext = isset($extMap[$mime]) ? $extMap[$mime] : pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = preg_replace('/[^a-z0-9]/', '', strtolower($ext));
    if (!$ext) $ext = 'bin';
    
    $filename = $userId . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    
    // Ensure directory exists
    $uploadDir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $destPath = $uploadDir . '/' . $filename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'url' => null, 'error' => 'Không thể lưu file'];
    }
    
    // Resize image if needed (only for images, skip GIF to preserve animation)
    if (in_array($mime, ['image/jpeg','image/png','image/webp']) && $resizeMax > 0) {
        _resize_image($destPath, $mime, $resizeMax);
    }
    
    // Strip EXIF for JPEG (privacy)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        _strip_exif($destPath);
    }
    
    $url = '/uploads/' . $folder . '/' . $filename;
    return ['success' => true, 'url' => $url, 'error' => null];
}

/**
 * Resize image to max dimension while keeping aspect ratio
 */
function _resize_image($path, $mime, $maxDim) {
    if (!function_exists('imagecreatefromjpeg')) return; // GD not available
    
    $info = @getimagesize($path);
    if (!$info) return;
    
    $w = $info[0];
    $h = $info[1];
    
    // Skip if already small enough
    if ($w <= $maxDim && $h <= $maxDim) return;
    
    // Calculate new dimensions
    if ($w > $h) {
        $newW = $maxDim;
        $newH = intval($h * $maxDim / $w);
    } else {
        $newH = $maxDim;
        $newW = intval($w * $maxDim / $h);
    }
    
    // Create source image
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png':  $src = @imagecreatefrompng($path); break;
        case 'image/webp': $src = @imagecreatefromwebp($path); break;
        default: return;
    }
    if (!$src) return;
    
    // Resize
    $dst = imagecreatetruecolor($newW, $newH);
    
    // Preserve transparency for PNG/WebP
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    
    // Save
    switch ($mime) {
        case 'image/jpeg': imagejpeg($dst, $path, 85); break;
        case 'image/png':  imagepng($dst, $path, 8); break;
        case 'image/webp': imagewebp($dst, $path, 85); break;
    }
    
    imagedestroy($src);
    imagedestroy($dst);
}

/**
 * Strip EXIF data from JPEG (privacy: removes GPS, camera info)
 */
function _strip_exif($path) {
    if (!function_exists('imagecreatefromjpeg')) return;
    $img = @imagecreatefromjpeg($path);
    if (!$img) return;
    imagejpeg($img, $path, 85);
    imagedestroy($img);
}

/**
 * Handle video upload (larger size, different types)
 */
function handle_video_upload($file, $folder, $userId = 0) {
    return handle_upload($file, $folder, [
        'user_id' => $userId,
        'max_size' => 50 * 1024 * 1024, // 50MB
        'allowed_types' => ['video/mp4', 'video/quicktime', 'video/webm'],
        'resize_max' => 0 // Don't resize video
    ]);
}
