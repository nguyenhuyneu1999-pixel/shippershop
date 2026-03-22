<?php
/**
 * ShipperShop Image Optimizer
 * Auto resize + compress images on upload
 * Reduces bandwidth 50-80% with minimal quality loss
 * Works on shared hosting (GD library)
 */

function optimizeImage($sourcePath, $destPath = null, $maxWidth = 1200, $quality = 82) {
    if (!$destPath) $destPath = $sourcePath;
    if (!file_exists($sourcePath)) return false;
    
    $info = @getimagesize($sourcePath);
    if (!$info) return false;
    
    $mime = $info['mime'];
    $origW = $info[0];
    $origH = $info[1];
    
    // Skip if already small enough
    if ($origW <= $maxWidth && filesize($sourcePath) < 200000) {
        return true; // Already optimized
    }
    
    // Load image
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $src = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false; // Unsupported format
    }
    
    if (!$src) return false;
    
    // Calculate new dimensions
    if ($origW > $maxWidth) {
        $newW = $maxWidth;
        $newH = intval($origH * ($maxWidth / $origW));
    } else {
        $newW = $origW;
        $newH = $origH;
    }
    
    // Resize
    $dst = imagecreatetruecolor($newW, $newH);
    
    // Preserve transparency for PNG
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    
    // Save
    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $saved = imagejpeg($dst, $destPath, $quality);
            break;
        case 'image/png':
            $saved = imagepng($dst, $destPath, 8); // 0-9, 8 = high compression
            break;
        case 'image/webp':
            $saved = imagewebp($dst, $destPath, $quality);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($dst);
    
    return $saved;
}

/**
 * Create thumbnail for feed preview (smaller, faster)
 */
function createThumbnail($sourcePath, $thumbPath, $maxWidth = 400, $quality = 75) {
    return optimizeImage($sourcePath, $thumbPath, $maxWidth, $quality);
}
