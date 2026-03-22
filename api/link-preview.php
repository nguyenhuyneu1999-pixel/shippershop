<?php
/**
 * ShipperShop Link Preview — Fetch OG tags from URL
 * GET /api/link-preview.php?url=https://example.com
 * Returns: {title, description, image, domain}
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$url = trim($_GET['url'] ?? '');
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid URL']);
    exit;
}

// Cache 1 hour
$cacheKey = 'link_' . md5($url);
api_try_cache($cacheKey, 3600);

$result = ['title' => '', 'description' => '', 'image' => '', 'domain' => parse_url($url, PHP_URL_HOST)];

$ctx = stream_context_create([
    'http' => ['timeout' => 5, 'user_agent' => 'ShipperShop/1.0 LinkPreview', 'follow_location' => 1, 'max_redirects' => 3],
    'ssl' => ['verify_peer' => false]
]);

$html = @file_get_contents($url, false, $ctx, 0, 50000);
if ($html) {
    // OG tags
    if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) $result['title'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) $result['description'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) $result['image'] = $m[1];
    
    // Fallback to title tag
    if (!$result['title'] && preg_match('/<title[^>]*>([^<]+)/i', $html, $m)) $result['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    // Fallback to description meta
    if (!$result['description'] && preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) $result['description'] = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
}

success('OK', $result);
