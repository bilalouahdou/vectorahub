<?php
// Test Checkout Session Creation
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: application/json');

// Start session for testing
startSession();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCsrfToken();
}

// Test database connection
try {
    $pdo = connectDB();
    $dbWorking = true;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscription_plans");
    $stmt->execute();
    $planCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $dbError = null;
} catch (Exception $e) {
    $dbWorking = false;
    $planCount = 0;
    $dbError = $e->getMessage();
}

echo json_encode([
    'stripe_publishable_key' => STRIPE_PUBLISHABLE_KEY ? 'SET' : 'NOT SET',
    'stripe_secret_key' => STRIPE_SECRET_KEY ? 'SET' : 'NOT SET',
    'stripe_webhook_secret' => STRIPE_WEBHOOK_SECRET ? 'SET' : 'NOT SET',
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'csrf_token' => $_SESSION['csrf_token'] ?? 'NOT SET',
    'app_url' => APP_URL,
    'server_https' => $_SERVER['HTTPS'] ?? 'not set',
    'forwarded_proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set',
    'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'not set',
    'user_logged_in' => isLoggedIn(),
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'database_working' => $dbWorking,
    'plan_count' => $planCount,
    'database_error' => $dbError
], JSON_PRETTY_PRINT);
?>
