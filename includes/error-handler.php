<?php
/**
 * ShipperShop Error Handler — Log errors to DB + file
 * Usage: require_once 'error-handler.php'; // Auto-registers handlers
 *        ss_log('error', 'Something failed', ['user_id'=>5]);
 */

/**
 * Log error/info to error_logs table
 * @param string $level debug|info|warning|error|critical
 * @param string $message
 * @param array $context optional context data
 */
function ss_log($level, $message, $context = []) {
    try {
        $d = db();
        $d->query(
            "INSERT INTO error_logs (level, message, file, line, user_id, url, ip, created_at) VALUES (?,?,?,?,?,?,?,NOW())",
            [
                $level,
                mb_substr($message, 0, 5000),
                isset($context['file']) ? $context['file'] : null,
                isset($context['line']) ? intval($context['line']) : null,
                isset($context['user_id']) ? intval($context['user_id']) : null,
                isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 500) : null,
                isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null
            ]
        );
    } catch (\Throwable $e) {
        // Fallback: log to file if DB fails
        $logDir = __DIR__ . '/../uploads/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $entry = date('Y-m-d H:i:s') . " [$level] $message " . json_encode($context) . "\n";
        @file_put_contents($logDir . '/error.log', $entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * PHP Error Handler — convert errors to log entries
 */
function _ss_error_handler($errno, $errstr, $errfile, $errline) {
    $levelMap = [
        E_ERROR => 'error', E_WARNING => 'warning', E_NOTICE => 'info',
        E_USER_ERROR => 'error', E_USER_WARNING => 'warning', E_USER_NOTICE => 'info',
    ];
    $level = isset($levelMap[$errno]) ? $levelMap[$errno] : 'warning';
    
    // Don't log suppressed errors (@)
    if (error_reporting() === 0) return false;
    
    ss_log($level, $errstr, ['file' => $errfile, 'line' => $errline]);
    
    // Don't execute PHP internal error handler for non-fatal
    return ($errno !== E_ERROR && $errno !== E_USER_ERROR);
}

/**
 * PHP Exception Handler — catch uncaught exceptions
 */
function _ss_exception_handler($e) {
    ss_log('critical', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    
    // Return generic error to user
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại.']);
}

/**
 * PHP Shutdown Handler — catch fatal errors
 */
function _ss_shutdown_handler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ss_log('critical', 'FATAL: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    }
}

// Register handlers (only for API, not for HTML pages)
if (defined('APP_ACCESS') || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)) {
    set_error_handler('_ss_error_handler');
    set_exception_handler('_ss_exception_handler');
    register_shutdown_function('_ss_shutdown_handler');
}
