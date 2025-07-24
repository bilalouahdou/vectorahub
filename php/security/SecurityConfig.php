<?php
/**
 * Security Configuration
 * Centralized security settings and constants
 */

class SecurityConfig {
    // Password requirements
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_REQUIRE_UPPERCASE = true;
    const PASSWORD_REQUIRE_LOWERCASE = true;
    const PASSWORD_REQUIRE_NUMBERS = true;
    const PASSWORD_REQUIRE_SPECIAL = true;
    
    // Session security
    const SESSION_TIMEOUT = 3600; // 1 hour
    const SESSION_REGENERATE_INTERVAL = 300; // 5 minutes
    const REMEMBER_ME_DURATION = 2592000; // 30 days
    
    // Rate limiting
    const RATE_LIMIT_LOGIN = 5; // attempts per window
    const RATE_LIMIT_LOGIN_WINDOW = 900; // 15 minutes
    const RATE_LIMIT_GENERAL = 60; // requests per minute
    const RATE_LIMIT_UPLOAD = 10; // uploads per hour
    
    // File upload security
    const MAX_UPLOAD_SIZE = 5242880; // 5MB
    const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png'];
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    const UPLOAD_SCAN_VIRUSES = false; // Set to true if antivirus available
    
    // Account lockout
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const PROGRESSIVE_LOCKOUT = true; // Increase lockout time with repeated failures
    
    // CSRF protection
    const CSRF_TOKEN_LIFETIME = 3600; // 1 hour
    const CSRF_TOKEN_REGENERATE = true; // Regenerate after each use
    
    // Security headers
    const ENABLE_HSTS = true;
    const HSTS_MAX_AGE = 31536000; // 1 year
    const ENABLE_CSP = true;
    const CSP_REPORT_URI = '/csp-report';
    
    // Logging and monitoring
    const LOG_SECURITY_EVENTS = true;
    const LOG_FAILED_LOGINS = true;
    const LOG_SUSPICIOUS_ACTIVITY = true;
    const ALERT_ON_CRITICAL_EVENTS = true;
    
    // API security (for future use)
    const API_RATE_LIMIT = 1000; // requests per hour
    const API_KEY_LENGTH = 32;
    const API_REQUIRE_HTTPS = true;
    
    // Database security
    const DB_USE_SSL = false; // Set to true in production
    const DB_VERIFY_SERVER_CERT = true;
    
    // Environment-specific settings
    public static function getEnvironmentConfig() {
        $environment = $_ENV['APP_ENV'] ?? 'production';
        
        switch ($environment) {
            case 'development':
                return [
                    'debug_mode' => true,
                    'log_level' => 'DEBUG',
                    'rate_limit_enabled' => false,
                    'csrf_enabled' => true,
                    'https_required' => false
                ];
                
            case 'staging':
                return [
                    'debug_mode' => false,
                    'log_level' => 'INFO',
                    'rate_limit_enabled' => true,
                    'csrf_enabled' => true,
                    'https_required' => true
                ];
                
            case 'production':
            default:
                return [
                    'debug_mode' => false,
                    'log_level' => 'WARNING',
                    'rate_limit_enabled' => true,
                    'csrf_enabled' => true,
                    'https_required' => true
                ];
        }
    }
    
    /**
     * Get security headers configuration
     */
    public static function getSecurityHeaders() {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Strict-Transport-Security' => self::ENABLE_HSTS ? 
                'max-age=' . self::HSTS_MAX_AGE . '; includeSubDomains; preload' : null
        ];
    }
    
    /**
     * Get Content Security Policy
     */
    public static function getCSP() {
        if (!self::ENABLE_CSP) {
            return null;
        }
        
        return [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self'",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];
    }
    
    /**
     * Validate configuration
     */
    public static function validateConfig() {
        $errors = [];
        
        // Check if HTTPS is available in production
        $env = self::getEnvironmentConfig();
        if ($env['https_required'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
            $errors[] = 'HTTPS is required but not available';
        }
        
        // Check if required PHP extensions are loaded
        $requiredExtensions = ['openssl', 'hash', 'filter', 'fileinfo'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = "Required PHP extension '$ext' is not loaded";
            }
        }
        
        // Check file permissions
        $uploadDir = dirname(__DIR__, 2) . '/uploads/';
        if (!is_writable($uploadDir)) {
            $errors[] = "Upload directory is not writable: $uploadDir";
        }
        
        return $errors;
    }
}
?>
