<?php

/**
 * Global Error Handler for VectraHub
 * Catches all errors and sends incident notifications
 */
class ErrorHandler {
    private static $emailService;
    private static $initialized = false;
    
    public static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        require_once __DIR__ . '/../services/EmailService.php';
        self::$emailService = new EmailService();
        
        // Set custom error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        self::$initialized = true;
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Only handle serious errors
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'Unknown Error';
        
        // Only send emails for serious errors
        if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            $errorMessage = "PHP $errorType: $errstr in $errfile on line $errline";
            self::sendIncidentEmail($errorMessage);
        }
        
        // Log the error
        error_log("[$errorType] $errstr in $errfile on line $errline");
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $errorMessage = "Uncaught Exception: " . $exception->getMessage() . 
                       " in " . $exception->getFile() . 
                       " on line " . $exception->getLine();
        
        self::sendIncidentEmail($errorMessage, $exception->getTraceAsString());
        
        // Log the exception
        error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Show user-friendly error page
        if (!headers_sent()) {
            http_response_code(500);
            if (self::isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'An unexpected error occurred. Please try again later.']);
            } else {
                self::showErrorPage();
            }
        }
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorMessage = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::sendIncidentEmail($errorMessage);
            
            // Show user-friendly error page
            if (!headers_sent()) {
                http_response_code(500);
                if (self::isJsonRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'A critical error occurred. Please try again later.']);
                } else {
                    self::showErrorPage();
                }
            }
        }
    }
    
    /**
     * Send incident email notification
     */
    private static function sendIncidentEmail($errorMessage, $stackTrace = null) {
        try {
            // Avoid sending too many emails (rate limiting)
            $cacheFile = __DIR__ . '/../../logs/error_email_cache.json';
            $now = time();
            $cache = [];
            
            if (file_exists($cacheFile)) {
                $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
            }
            
            // Clean old entries (older than 1 hour)
            $cache = array_filter($cache, function($timestamp) use ($now) {
                return ($now - $timestamp) < 3600;
            });
            
            // Create error hash to avoid duplicate emails
            $errorHash = md5($errorMessage);
            
            if (isset($cache[$errorHash])) {
                // Already sent email for this error in the last hour
                return;
            }
            
            // Get user and request info
            $userInfo = self::getUserInfo();
            $requestInfo = self::getRequestInfo();
            
            if ($stackTrace) {
                $errorMessage .= "\n\nStack Trace:\n" . $stackTrace;
            }
            
            // Send incident email
            self::$emailService->sendIncidentEmail($errorMessage, $userInfo, $requestInfo);
            
            // Update cache
            $cache[$errorHash] = $now;
            file_put_contents($cacheFile, json_encode($cache), LOCK_EX);
            
        } catch (Exception $e) {
            // Prevent infinite loop - just log if email fails
            error_log("Failed to send incident email: " . $e->getMessage());
        }
    }
    
    /**
     * Get current user information
     */
    private static function getUserInfo() {
        $info = [];
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $info['user_id'] = $_SESSION['user_id'];
            $info['user_email'] = $_SESSION['user_email'] ?? 'unknown';
        } else {
            $info['user'] = 'anonymous';
        }
        
        $info['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $info['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $info;
    }
    
    /**
     * Get request information
     */
    private static function getRequestInfo() {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none',
            'timestamp' => date('Y-m-d H:i:s'),
            'post_data' => !empty($_POST) ? array_keys($_POST) : [],
            'get_data' => !empty($_GET) ? array_keys($_GET) : []
        ];
    }
    
    /**
     * Check if request expects JSON response
     */
    private static function isJsonRequest() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        return strpos($contentType, 'application/json') !== false ||
               strpos($acceptHeader, 'application/json') !== false ||
               isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }
    
    /**
     * Show user-friendly error page
     */
    private static function showErrorPage() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - VectraHub</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                }
                .error-card {
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card error-card">
                            <div class="card-body text-center p-5">
                                <div class="mb-4">
                                    <div style="font-size: 4rem; color: #dc3545;">⚠️</div>
                                </div>
                                <h1 class="h3 mb-3">Oops! Something went wrong</h1>
                                <p class="text-muted mb-4">
                                    We're experiencing technical difficulties. Our team has been notified and is working to fix the issue.
                                </p>
                                <div class="d-grid gap-2 d-md-block">
                                    <a href="/" class="btn btn-primary">Go Home</a>
                                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
?>
