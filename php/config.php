<?php

// --- Application Settings ---
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development', 'production'
define('APP_NAME', getenv('APP_NAME') ?: 'VectraHub');
define('APP_URL', getenv('APP_URL') ?: 'https://vectrahub.online');

// Environment variables are now handled by bootstrap.php
// This ensures Apache-provided variables are never overridden

// --- Database Configuration (Supabase PostgreSQL) ---
// Prioritize DATABASE_URL if available (e.g., from Fly.io)
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $dbParts = parse_url($databaseUrl);
    define('DB_HOST', $dbParts['host']);
    define('DB_PORT', $dbParts['port'] ?? '5432');
    define('DB_NAME', ltrim($dbParts['path'], '/'));
    define('DB_USER', $dbParts['user']);
    define('DB_PASSWORD', $dbParts['pass']);
    define('DB_SSLMODE', 'require'); // Supabase requires SSL
} else {
    // Fallback to individual environment variables
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: ''); // IMPORTANT: Set this via Fly secrets!
    define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'prefer'); // 'require' for production, 'prefer' for local
}

// --- Email Configuration (Resend) ---
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: 're_hzYtNgk1_HW2JkU65FMuKf1fcwf3TAVcd');

// --- Cron Security ---
define('CRON_SECRET_KEY', getenv('CRON_SECRET_KEY') ?: bin2hex(random_bytes(16)));

// --- Supabase API Keys (for client-side if needed, or server-side interactions) ---
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'YOUR_SUPABASE_URL'); // e.g., https://xyz.supabase.co
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: 'YOUR_SUPABASE_ANON_KEY');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: 'YOUR_SUPABASE_SERVICE_ROLE_KEY'); // Keep this secret!

// --- Stripe API Keys ---
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// --- External Runner (GPU/Vectorize) ---
// Cloudflared/Salad endpoint and auth shared secret
define('RUNNER_BASE_URL', rtrim(getenv('RUNNER_BASE_URL') ?: '', '/'));
define('RUNNER_SHARED_TOKEN', getenv('RUNNER_SHARED_TOKEN') ?: '');
define('RUNNER_TIMEOUT_SECONDS', (int)(getenv('RUNNER_TIMEOUT_SECONDS') ?: 120));

// --- File Upload Settings ---
define('UPLOAD_MAX_SIZE', (int)(getenv('UPLOAD_MAX_SIZE') ?: 5 * 1024 * 1024)); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('OUTPUT_DIR', __DIR__ . '/../outputs/');
define('TEMP_DIR', __DIR__ . '/../temp/');

// --- Session and Security Settings ---
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 86400)); // 1 day in seconds
define('CSRF_TOKEN_EXPIRY', (int)(getenv('CSRF_TOKEN_EXPIRY') ?: 3600)); // 1 hour in seconds

// Configure session (only if session hasn't started and headers haven't been sent)
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);

    // Set secure cookie for production
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }
}

// --- Error Reporting ---
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// --- Database Connection Function ---
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=" . DB_SSLMODE;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Log the error without exposing sensitive details
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// --- Test Database Connection ---
function testDatabaseConnection() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query('SELECT version()');
        $version = $stmt->fetchColumn();
        return ['success' => true, 'message' => 'Connected to PostgreSQL', 'version' => $version];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// --- Start Session ---
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// --- CSRF Token Functions ---
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || ($_SESSION['csrf_token_expiry'] ?? 0) < time()) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + CSRF_TOKEN_EXPIRY;
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_expiry'])) {
        return false;
    }
    if ($_SESSION['csrf_token_expiry'] < time()) {
        return false; // Token expired
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// --- Redirection Function ---
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// --- Logging Function ---
function logMessage($level, $message) {
    $logFile = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Ensure upload, output, temp directories exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0755, true);
}
if (!is_dir(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0755, true);
}

// Start session automatically
startSession();

?>
