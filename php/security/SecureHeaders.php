<?php
/**
 * Security Headers Manager
 * Implements comprehensive security headers
 */

class SecureHeaders {
    private static $headers = [
        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',
        
        // Prevent clickjacking
        'X-Frame-Options' => 'DENY',
        
        // XSS Protection
        'X-XSS-Protection' => '1; mode=block',
        
        // Referrer Policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        
        // Permissions Policy (formerly Feature Policy)
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        
        // Strict Transport Security (HTTPS only)
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        
        // Expect-CT (Certificate Transparency)
        'Expect-CT' => 'max-age=86400, enforce'
    ];
    
    /**
     * Set all security headers
     */
    public static function setSecurityHeaders() {
        // Only set HSTS if using HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            unset(self::$headers['Strict-Transport-Security']);
            unset(self::$headers['Expect-CT']);
        }
        
        foreach (self::$headers as $header => $value) {
            header("$header: $value");
        }
        
        // Set Content Security Policy
        self::setContentSecurityPolicy();
    }
    
    /**
     * Set Content Security Policy
     */
    private static function setContentSecurityPolicy() {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://www.google-analytics.com",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];
        
        $cspHeader = implode('; ', $csp);
        header("Content-Security-Policy: $cspHeader");
        
        // Also set as report-only for monitoring
        header("Content-Security-Policy-Report-Only: $cspHeader; report-uri /csp-report");
    }
    
    /**
     * Set cache control headers
     */
    public static function setCacheHeaders($type = 'no-cache') {
        switch ($type) {
            case 'no-cache':
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                break;
                
            case 'static':
                header('Cache-Control: public, max-age=31536000'); // 1 year
                break;
                
            case 'dynamic':
                header('Cache-Control: private, max-age=3600'); // 1 hour
                break;
        }
    }
    
    /**
     * Set CORS headers (if needed for API)
     */
    public static function setCORSHeaders($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: null");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 hours
    }
}

// Auto-apply security headers
SecureHeaders::setSecurityHeaders();
?>
