<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Bilal12345@');
define('DB_NAME', 'vector');

// App Configuration
$APP_NAME = 'VectraHub';
$APP_URL = 'https://vectorahub.online'; // Updated domain
$UPLOAD_DIR = __DIR__ . '/../uploads/';
$OUTPUT_DIR = __DIR__ . '/../outputs/';
$PYTHON_SCRIPT = __DIR__ . '/../python/trace_with_tolerance_pil.py';

// Python API Configuration
$PYTHON_API_URL = 'http://localhost:5000';

// Stripe Configuration (Replace with your actual keys)
$STRIPE_PUBLISHABLE_KEY = 'pk_live_51LEv8eJYJk34NKovrcBUReHSZ1rCRC7wtiN9MBqtZOI0kXKGiSEzAk8p8nvrrKLcwSLJ6VAZxnfIl8Nc3QZ4XGbP00mxRAdzjW';
$STRIPE_SECRET_KEY = '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';
$STRIPE_WEBHOOK_SECRET = 'whsec_your_webhook_secret_here';

// Security Configuration
$CSRF_TOKEN_EXPIRY = 3600; // 1 hour
$SESSION_LIFETIME = 86400; // 24 hours
$MAX_LOGIN_ATTEMPTS = 5;
$LOGIN_LOCKOUT_TIME = 900; // 15 minutes

// File Upload Limits
$MAX_FILE_SIZE = 50 * 1024 * 1024; // 5MB
$ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg'];

// Rate Limiting
$RATE_LIMIT_REQUESTS = 10; // requests per minute
$RATE_LIMIT_WINDOW = 60; // seconds

// Email Configuration (for notifications)
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_USERNAME = 'your-email@gmail.com';
$SMTP_PASSWORD = 'your-app-password';
$FROM_EMAIL = 'noreply@vectorahub.online';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Security Headers - Set before starting session
    session_set_cookie_params([
        'lifetime' => $SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create necessary directories
if (!file_exists($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0755, true);
}
if (!file_exists($OUTPUT_DIR)) {
    mkdir($OUTPUT_DIR, 0755, true);
}

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// NOTE: Helper functions like isLoggedIn() and isAdmin() are defined in utils.php
// Do not redeclare them here to avoid conflicts
?>
