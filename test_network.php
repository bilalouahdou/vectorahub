<?php
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: application/json');

startSession();

// Test if user is logged in
$isLoggedIn = isLoggedIn();
$userId = $_SESSION['user_id'] ?? null;

// Test database connection
try {
    $pdo = connectDB();
    $dbWorking = true;
    
    // Test getting plans
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscription_plans");
    $stmt->execute();
    $planCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $dbWorking = false;
    $planCount = 0;
    $dbError = $e->getMessage();
}

// Test Stripe keys
$stripeKeys = [
    'publishable' => STRIPE_PUBLISHABLE_KEY ? 'SET (' . substr(STRIPE_PUBLISHABLE_KEY, 0, 10) . '...)' : 'NOT SET',
    'secret' => STRIPE_SECRET_KEY ? 'SET (' . substr(STRIPE_SECRET_KEY, 0, 10) . '...)' : 'NOT SET',
    'webhook' => STRIPE_WEBHOOK_SECRET ? 'SET (' . substr(STRIPE_WEBHOOK_SECRET, 0, 10) . '...)' : 'NOT SET'
];

// Test CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? null;
if (!$csrfToken) {
    $_SESSION['csrf_token'] = generateCsrfToken();
    $csrfToken = $_SESSION['csrf_token'];
}

$response = [
    'server_time' => date('Y-m-d H:i:s'),
    'session_status' => [
        'started' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'logged_in' => $isLoggedIn,
        'user_id' => $userId
    ],
    'database' => [
        'working' => $dbWorking,
        'plan_count' => $planCount,
        'error' => $dbError ?? null
    ],
    'stripe_keys' => $stripeKeys,
    'csrf_token' => [
        'exists' => !empty($csrfToken),
        'value' => $csrfToken ? substr($csrfToken, 0, 10) . '...' : 'NOT SET'
    ],
    'app_url' => APP_URL,
    'php_errors' => error_get_last()
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

