<?php
// API Response Time Middleware
// Adds X-Response-Time header and optional Server-Timing
$_SERVER['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

function api_timer_finish() {
    $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $ms = round((microtime(true) - $start) * 1000, 1);
    if (!headers_sent()) {
        header('X-Response-Time: ' . $ms . 'ms');
        header('Server-Timing: total;dur=' . $ms);
    }
}
register_shutdown_function('api_timer_finish');
