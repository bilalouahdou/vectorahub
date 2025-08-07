<?php
// Debug Checkout Issues
require_once 'php/config.php';
require_once 'php/utils.php';

header('Content-Type: application/json');

startSession();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCsrfToken();
}

// Simulate a checkout session creation to debug the issue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Debug Checkout - POST data: " . print_r($_POST, true));
    
    $csrfToken = $_POST['csrf_token'] ?? '';
    $planId = $_POST['plan_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    $debug = [
        'csrf_token_received' => $csrfToken,
        'csrf_token_session' => $_SESSION['csrf_token'] ?? 'NOT SET',
        'csrf_token_valid' => verifyCsrfToken($csrfToken),
        'plan_id' => $planId,
        'user_id' => $userId,
        'session_data' => $_SESSION,
        'post_data' => $_POST,
        'stripe_keys' => [
            'publishable' => STRIPE_PUBLISHABLE_KEY ? 'SET' : 'NOT SET',
            'secret' => STRIPE_SECRET_KEY ? 'SET' : 'NOT SET'
        ]
    ];
    
    if (!$planId || !$userId) {
        $debug['error'] = 'Missing plan ID or user ID';
        echo json_encode($debug);
        exit;
    }
    
    if (!verifyCsrfToken($csrfToken)) {
        $debug['error'] = 'CSRF token validation failed';
        echo json_encode($debug);
        exit;
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $debug['plan_found'] = $plan ? true : false;
        $debug['plan_data'] = $plan;
        
        if (!$plan) {
            $debug['error'] = 'Plan not found';
            echo json_encode($debug);
            exit;
        }
        
        $debug['success'] = 'All validations passed - would create Stripe session';
        echo json_encode($debug);
        
    } catch (Exception $e) {
        $debug['error'] = 'Database error: ' . $e->getMessage();
        echo json_encode($debug);
    }
} else {
    // GET request - show debug info
    echo json_encode([
        'method' => 'GET',
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'csrf_token' => $_SESSION['csrf_token'] ?? 'NOT SET',
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
        'stripe_keys' => [
            'publishable' => STRIPE_PUBLISHABLE_KEY ? 'SET' : 'NOT SET',
            'secret' => STRIPE_SECRET_KEY ? 'SET' : 'NOT SET'
        ]
    ]);
}
?>

