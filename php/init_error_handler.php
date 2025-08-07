<?php
/**
 * Initialize global error handler for VectraHub
 * Include this at the top of every entry point (index.php, API endpoints, etc.)
 */

require_once __DIR__ . '/middleware/ErrorHandler.php';

// Initialize error handling
ErrorHandler::initialize();

// Set error reporting based on environment
if (defined('APP_ENV') && APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

// Set log file
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
?>
