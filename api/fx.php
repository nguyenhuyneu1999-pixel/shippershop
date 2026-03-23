<?php
header('Content-Type: application/json');
echo json_encode([
    'litespeed' => strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'LiteSpeed') !== false,
    'lscache_available' => class_exists('LiteSpeedCache') || function_exists('litespeed_finish_request'),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'htaccess_mod' => file_exists(__DIR__ . '/../.htaccess') ? 'yes' : 'no',
]);
