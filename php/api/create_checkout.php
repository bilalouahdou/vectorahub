<?php
/**
 * Hardened Checkout API Endpoint
 * 
 * This endpoint handles Stripe checkout session creation with:
 * - CSRF token validation
 * - Guaranteed JSON responses
 * - Proper error handling
 * - Content-Type headers
 */

// Prevent any output before headers
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON headers immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token invalid'
        ]);
        exit;
    }

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated'
        ]);
        exit;
    }

    // Validate plan ID
    $planId = $_POST['plan_id'] ?? null;
    if (!$planId || !is_numeric($planId)) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Invalid plan ID'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get plan from database
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Plan not found'
        ]);
        exit;
    }

    // Initialize Stripe
    require_once __DIR__ . '/../../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Create checkout session data
    $sessionData = [
        'success_url' => 'https://vectrahub.online/billing?success=true',
        'cancel_url' => 'https://vectrahub.online/billing?canceled=true',
        'metadata' => [
            'user_id' => $userId,
            'plan_id' => $planId,
        ],
    ];

    // Configure based on plan type
    if (!empty($plan['stripe_price_id']) && $plan['price'] > 0) {
        // Subscription plan
        $sessionData['line_items'] = [[
            'price' => $plan['stripe_price_id'],
            'quantity' => 1,
        ]];
        $sessionData['mode'] = 'subscription';
        $sessionData['subscription_data'] = [
            'metadata' => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
        ];
    } else {
        // One-time payment (for free plans or special cases)
        $sessionData['line_items'] = [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $plan['name'],
                ],
                'unit_amount' => max(100, $plan['price'] * 100), // Minimum $1
            ],
            'quantity' => 1,
        ]];
        $sessionData['mode'] = 'payment';
    }

    // Create Stripe checkout session
    $checkout_session = \Stripe\Checkout\Session::create($sessionData);

    // Log successful checkout creation
    logActivity(
        'CHECKOUT_CREATED',
        "Checkout session created for plan: {$plan['name']} (ID: {$planId})",
        $userId
    );

    // Clear any unexpected output and return success
    ob_clean();
    echo json_encode([
        'success' => true,
        'session_id' => $checkout_session->id
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    // Log Stripe API errors
    error_log("Stripe API Error: " . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Payment system error'
    ]);
} catch (Exception $e) {
    // Log general errors
    error_log("Checkout Error: " . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
}

// Ensure output buffer is flushed
ob_end_flush();
?>
