<?php
// Proxy to messages-api.php with DEBUG_MODE=true
define('DEBUG_MODE', true);

// Forward all headers, method, input
$_GET['action'] = $_GET['action'] ?? 'send';
include __DIR__ . '/messages-api.php';
