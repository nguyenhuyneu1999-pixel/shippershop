<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

// Simple auth check
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$token) { echo json_encode(['error' => 'auth']); exit; }

$logDir = __DIR__ . '/../uploads/analytics';
$days = intval($_GET['days'] ?? 7);
$data = [];

for ($i = 0; $i < $days; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $file = "$logDir/$date.log";
    if (!file_exists($file)) continue;
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $pages = [];
    $views = 0;
    $uniqIps = [];
    $refs = [];
    
    foreach ($lines as $line) {
        $parts = explode("\t", $line);
        if (count($parts) < 5) continue;
        $page = $parts[1] ?? '';
        $action = $parts[2] ?? 'view';
        $ref = $parts[3] ?? '';
        $ip = $parts[6] ?? '';
        
        if ($action === 'view') {
            $views++;
            $pages[$page] = ($pages[$page] ?? 0) + 1;
            $uniqIps[$ip] = 1;
            
            if ($ref && strpos($ref, 'shippershop.vn') === false) {
                $domain = parse_url($ref, PHP_URL_HOST) ?: $ref;
                $refs[$domain] = ($refs[$domain] ?? 0) + 1;
            }
        }
    }
    
    arsort($pages);
    arsort($refs);
    
    $data[] = [
        'date' => $date,
        'views' => $views,
        'unique_visitors' => count($uniqIps),
        'top_pages' => array_slice($pages, 0, 10, true),
        'top_referrers' => array_slice($refs, 0, 5, true),
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
