<?php
// Fixed checkout session - minimal but working
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Set JSON header first
header('Content-Type: application/json');

// Simple error handling without suppression
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

try {
    startSession();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError('Invalid request method');
    }
    
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonError('CSRF token validation failed');
    }
    
    $planId = $_POST['plan_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$planId || !$userId) {
        jsonError('Missing plan ID or user ID');
    }
    
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        jsonError('Subscription plan not found');
    }
    
    require_once __DIR__ . '/../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Determine if this is a subscription or one-time payment
    $isSubscription = !empty($plan['stripe_price_id']) && $plan['price'] > 0;
    
    if ($isSubscription) {
        // Create subscription checkout session
        $checkout_session = \Stripe\Checkout\Session::create([
            'line_items' => [[
                'price' => $plan['stripe_price_id'],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => APP_URL . '/billing?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => APP_URL . '/billing?canceled=true',
            'metadata' => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                ],
            ],
        ]);
    } else {
        // Create one-time payment checkout session
        $checkout_session = \Stripe\Checkout\Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $plan['name'],
                        'description' => $plan['features'],
                    ],
                    'unit_amount' => $plan['price'] * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => APP_URL . '/billing?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => APP_URL . '/billing?canceled=true',
            'metadata' => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'session_id' => $checkout_session->id
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    jsonError('Stripe API error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage(), 500);
}
?>
