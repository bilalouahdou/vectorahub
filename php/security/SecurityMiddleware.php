<?php
/**
 * Security Middleware
 * Applied to all requests for comprehensive security
 */

require_once 'SecurityManager.php';
require_once 'SecureHeaders.php';

class SecurityMiddleware {
    private $security;
    private $excludedPaths = ['/csp-report', '/health-check'];
    
    public function __construct() {
        $this->security = SecurityManager::getInstance();
    }
    
    /**
     * Process incoming request
     */
    public function process() {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Skip security checks for excluded paths
        if ($this->isExcludedPath($requestUri)) {
            return true;
        }
        
        // Apply security headers
        SecureHeaders::setSecurityHeaders();
        
        // Check rate limiting
        if (!$this->checkRateLimit()) {
            $this->sendErrorResponse(429, 'Too Many Requests');
            return false;
        }
        
        // Validate request size
        if (!$this->validateRequestSize()) {
            $this->sendErrorResponse(413, 'Request Entity Too Large');
            return false;
        }
        
        // Check for suspicious patterns
        if ($this->containsSuspiciousPatterns()) {
            $this->security->logSecurityEvent('SUSPICIOUS_REQUEST', [
                'uri' => $requestUri,
                'method' => $requestMethod,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ], 'WARNING');
            
            $this->sendErrorResponse(400, 'Bad Request');
            return false;
        }
        
        // Validate CSRF for POST requests
        if ($requestMethod === 'POST' && !$this->validateCSRF()) {
            $this->sendErrorResponse(403, 'CSRF Token Invalid');
            return false;
        }
        
        // Check session security
        if (!$this->validateSession()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if path is excluded from security checks
     */
    private function isExcludedPath($path) {
        foreach ($this->excludedPaths as $excluded) {
            if (strpos($path, $excluded) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Rate limiting check
     */
    private function checkRateLimit() {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Combine IP and User Agent for more accurate limiting
        $identifier = md5($clientIP . $userAgent);
        
        return $this->security->checkRateLimit($identifier, 'general', 60, 60); // 60 requests per minute
    }
    
    /**
     * Validate request size
     */
    private function validateRequestSize() {
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
        if ($contentLength > $maxSize) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check for suspicious patterns in request
     */
    private function containsSuspiciousPatterns() {
        $suspiciousPatterns = [
            // SQL Injection patterns
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            
            // XSS patterns
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            
            // Path traversal
            '/\.\.\//i',
            '/\.\.\\/i',
            
            // Command injection
            '/;\s*cat\s+/i',
            '/;\s*ls\s+/i',
            '/;\s*rm\s+/i',
            '/;\s*wget\s+/i',
            '/;\s*curl\s+/i',
            
            // PHP code injection
            '/<\?php/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i',
            '/passthru/i'
        ];
        
        // Check all input sources
        $inputSources = [
            $_GET,
            $_POST,
            $_COOKIE,
            [$_SERVER['REQUEST_URI'] ?? ''],
            [$_SERVER['HTTP_USER_AGENT'] ?? ''],
            [$_SERVER['HTTP_REFERER'] ?? '']
        ];
        
        foreach ($inputSources as $source) {
            if (is_array($source)) {
                foreach ($source as $value) {
                    if (is_string($value)) {
                        foreach ($suspiciousPatterns as $pattern) {
                            if (preg_match($pattern, $value)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Validate CSRF token for POST requests
     */
    private function validateCSRF() {
        // Skip CSRF validation for API endpoints with proper authentication
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            return true; // API should use different authentication
        }
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $this->security->validateCSRFToken($token);
    }
    
    /**
     * Validate session security
     */
    private function validateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Check session timeout
            if (!$this->security->checkSessionTimeout()) {
                session_destroy();
                if ($this->isAjaxRequest()) {
                    $this->sendErrorResponse(401, 'Session Expired');
                    return false;
                } else {
                    header('Location: /login.php');
                    exit;
                }
            }
            
            // Check session hijacking
            if (!$this->validateSessionFingerprint()) {
                session_destroy();
                $this->security->logSecurityEvent('SESSION_HIJACK_ATTEMPT', [
                    'user_id' => $_SESSION['user_id'] ?? null
                ], 'CRITICAL');
                
                if ($this->isAjaxRequest()) {
                    $this->sendErrorResponse(401, 'Session Invalid');
                    return false;
                } else {
                    header('Location: /login.php');
                    exit;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate session fingerprint to prevent hijacking
     */
    private function validateSessionFingerprint() {
        $currentFingerprint = $this->generateSessionFingerprint();
        $storedFingerprint = $_SESSION['fingerprint'] ?? '';
        
        if (empty($storedFingerprint)) {
            $_SESSION['fingerprint'] = $currentFingerprint;
            return true;
        }
        
        return hash_equals($storedFingerprint, $currentFingerprint);
    }
    
    /**
     * Generate session fingerprint
     */
    private function generateSessionFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Send error response
     */
    private function sendErrorResponse($code, $message) {
        http_response_code($code);
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message, 'code' => $code]);
        } else {
            echo "<h1>$code - $message</h1>";
        }
        
        exit;
    }
}

// Initialize security middleware for all requests
$securityMiddleware = new SecurityMiddleware();
if (!$securityMiddleware->process()) {
    exit;
}
?>
