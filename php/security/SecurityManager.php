<?php
/**
 * Centralized Security Manager for VectorizeAI
 * Implements security-by-design principles
 */

class SecurityManager {
    private static $instance = null;
    private $config;
    private $rateLimiter;
    private $csrfManager;
    
    private function __construct() {
        $this->config = [
            'max_upload_size' => 5 * 1024 * 1024, // 5MB
            'allowed_mime_types' => ['image/jpeg', 'image/png'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png'],
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'session_timeout' => 3600, // 1 hour
            'rate_limit_requests' => 10,
            'rate_limit_window' => 60 // 1 minute
        ];
        
        $this->initializeComponents();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeComponents() {
        $this->rateLimiter = new RateLimiter();
        $this->csrfManager = new CSRFManager();
    }
    
    /**
     * Comprehensive input validation and sanitization
     */
    public function validateAndSanitizeInput($input, $type = 'string', $options = []) {
        // Remove null bytes and control characters
        $input = str_replace(["\0", "\x0B"], '', $input);
        
        switch ($type) {
            case 'email':
                $input = filter_var(trim($input), FILTER_VALIDATE_EMAIL);
                break;
                
            case 'url':
                $input = filter_var(trim($input), FILTER_VALIDATE_URL);
                if ($input && !in_array(parse_url($input, PHP_URL_SCHEME), ['http', 'https'])) {
                    return false;
                }
                break;
                
            case 'filename':
                // Sanitize filename to prevent directory traversal
                $input = basename($input);
                $input = preg_replace('/[^a-zA-Z0-9._-]/', '_', $input);
                $input = trim($input, '.');
                break;
                
            case 'string':
            default:
                $input = htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                break;
        }
        
        // Length validation
        if (isset($options['max_length']) && strlen($input) > $options['max_length']) {
            return false;
        }
        
        if (isset($options['min_length']) && strlen($input) < $options['min_length']) {
            return false;
        }
        
        return $input;
    }
    
    /**
     * Advanced file upload validation
     */
    public function validateFileUpload($file) {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $this->getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // File size validation
        if ($file['size'] > $this->config['max_upload_size']) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // MIME type validation using multiple methods
        $mimeType = $this->detectMimeType($file['tmp_name']);
        if (!in_array($mimeType, $this->config['allowed_mime_types'])) {
            $errors[] = 'Invalid file type. Only JPEG and PNG images are allowed';
        }
        
        // File extension validation
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_extensions'])) {
            $errors[] = 'Invalid file extension';
        }
        
        // Image validation - ensure it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            $errors[] = 'File is not a valid image';
        }
        
        // Check for embedded PHP code or suspicious content
        if ($this->containsSuspiciousContent($file['tmp_name'])) {
            $errors[] = 'File contains suspicious content';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'image_info' => $imageInfo
        ];
    }
    
    /**
     * Detect MIME type using multiple methods for accuracy
     */
    private function detectMimeType($filePath) {
        // Method 1: finfo (most reliable)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            if ($mimeType) return $mimeType;
        }
        
        // Method 2: mime_content_type (fallback)
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            if ($mimeType) return $mimeType;
        }
        
        // Method 3: getimagesize for images
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo && isset($imageInfo['mime'])) {
            return $imageInfo['mime'];
        }
        
        return 'application/octet-stream'; // Default fallback
    }
    
    /**
     * Check for suspicious content in uploaded files
     */
    private function containsSuspiciousContent($filePath) {
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i',
            '/passthru/i',
            '/base64_decode/i'
        ];
        
        $content = file_get_contents($filePath, false, null, 0, 8192); // Read first 8KB
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($originalName, $prefix = 'upload') {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Password strength validation
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check against common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common. Please choose a more unique password';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password)
        ];
    }
    
    /**
     * Check if password is in common passwords list
     */
    private function isCommonPassword($password) {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Calculate password strength score
     */
    private function calculatePasswordStrength($password) {
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 8) $score += 1;
        if ($length >= 12) $score += 1;
        if ($length >= 16) $score += 1;
        
        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[0-9]/', $password)) $score += 1;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 1;
        
        // Complexity bonus
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 1;
        
        return min($score, 5); // Max score of 5
    }
    
    /**
     * Rate limiting implementation
     */
    public function checkRateLimit($identifier, $action = 'general') {
        return $this->rateLimiter->checkLimit($identifier, $action);
    }
    
    /**
     * CSRF token management
     */
    public function generateCSRFToken() {
        return $this->csrfManager->generateToken();
    }
    
    public function validateCSRFToken($token) {
        return $this->csrfManager->validateToken($token);
    }
    
    /**
     * Secure session management
     */
    public function initializeSecureSession() {
        // Regenerate session ID to prevent fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        
        // Set session timeout
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check session timeout
     */
    public function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->config['session_timeout']) {
                session_destroy();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        return true;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event, $details = [], $severity = 'INFO') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'details' => $details
        ];
        
        error_log('[SECURITY] ' . json_encode($logEntry));
        
        // For high severity events, consider additional alerting
        if (in_array($severity, ['ERROR', 'CRITICAL'])) {
            $this->sendSecurityAlert($logEntry);
        }
    }
    
    /**
     * Send security alerts for critical events
     */
    private function sendSecurityAlert($logEntry) {
        // Implement email/webhook notifications for critical security events
        // This could integrate with services like Slack, Discord, or email
    }
    
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
}

/**
 * Rate Limiter Implementation
 */
class RateLimiter {
    private $storage;
    
    public function __construct() {
        // Use file-based storage for simplicity, can be upgraded to Redis later
        $this->storage = sys_get_temp_dir() . '/rate_limits/';
        if (!is_dir($this->storage)) {
            mkdir($this->storage, 0755, true);
        }
    }
    
    public function checkLimit($identifier, $action = 'general', $maxRequests = 10, $windowSeconds = 60) {
        $key = md5($identifier . '_' . $action);
        $file = $this->storage . $key . '.json';
        
        $now = time();
        $data = [];
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?: [];
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $data[] = $now;
        file_put_contents($file, json_encode($data));
        
        return true;
    }
}

/**
 * CSRF Manager Implementation
 */
class CSRFManager {
    private $tokenName = 'csrf_token';
    private $tokenLifetime = 3600; // 1 hour
    
    public function generateToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->tokenName] = [
            'token' => $token,
            'timestamp' => time()
        ];
        return $token;
    }
    
    public function validateToken($token) {
        if (!isset($_SESSION[$this->tokenName])) {
            return false;
        }
        
        $sessionData = $_SESSION[$this->tokenName];
        
        // Check token expiry
        if (time() - $sessionData['timestamp'] > $this->tokenLifetime) {
            unset($_SESSION[$this->tokenName]);
            return false;
        }
        
        // Validate token
        if (!hash_equals($sessionData['token'], $token)) {
            return false;
        }
        
        // Token is valid, regenerate for next use
        $this->generateToken();
        return true;
    }
}
?>
