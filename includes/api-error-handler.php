<?php
/**
 * ShipperShop API Error Handler
 * Catch all errors, return clean JSON, log for debug
 * Prevents 500 errors showing raw PHP errors to users
 */

function setupApiErrorHandler() {
    // Convert PHP errors to exceptions
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) return false;
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    
    // Catch fatal errors
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            $msg = defined('DEBUG_MODE') && DEBUG_MODE ? $error['message'] : 'Internal server error';
            echo json_encode(['success' => false, 'message' => $msg]);
            // Log
            error_log("FATAL: {$error['message']} in {$error['file']}:{$error['line']}");
        }
    });
    
    // Global exception handler
    set_exception_handler(function($e) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        $msg = defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : 'Server error';
        echo json_encode(['success' => false, 'message' => $msg]);
        error_log("EXCEPTION: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    });
}
