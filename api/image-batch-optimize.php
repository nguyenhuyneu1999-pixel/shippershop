<?php
/**
 * ShipperShop — Batch optimize existing uploaded images
 * Run ONCE: /api/image-batch-optimize.php?key=ss_img_opt&batch=50
 * Compresses all images in uploads/posts/ that are > 200KB
 */
if (($_GET['key'] ?? '') !== 'ss_img_opt') { http_response_code(403); exit; }
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image-optimizer.php';

header('Content-Type: application/json');
set_time_limit(120);

$dir = __DIR__ . '/../uploads/posts/';
$batch = min(intval($_GET['batch'] ?? 20), 100);
$minSize = 200000; // Only optimize files > 200KB

$files = glob($dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$optimized = 0;
$skipped = 0;
$saved = 0;

foreach ($files as $f) {
    if ($optimized >= $batch) break;
    $size = filesize($f);
    if ($size < $minSize) { $skipped++; continue; }
    
    $before = $size;
    $result = optimizeImage($f, $f, 1200, 82);
    clearstatcache(true, $f);
    $after = filesize($f);
    
    if ($result && $after < $before) {
        $saved += ($before - $after);
        $optimized++;
    } else {
        $skipped++;
    }
}

echo json_encode([
    'success' => true,
    'optimized' => $optimized,
    'skipped' => $skipped,
    'saved_bytes' => $saved,
    'saved_mb' => round($saved / 1048576, 2),
    'total_files' => count($files),
    'remaining' => count($files) - $optimized - $skipped
]);
