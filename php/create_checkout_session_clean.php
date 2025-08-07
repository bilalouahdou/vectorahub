<?php
// Clean checkout session creation with error suppression
error_reporting(0); // Suppress all errors for clean JSON output
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

try {
    require_once '../config.php';
    require_once '../utils.php';
    
    // Clear any previous output
    ob_clean();
    
    header('Content-Type: application/json');
    
    startSession();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            echo json_encode(['success' => false, 'error' => 'CSRF token validation failed.']);
            exit;
        }
        
        $planId = $_POST['plan_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$planId || !$userId) {
            echo json_encode(['success' => false, 'error' => 'Missing plan ID or user ID.']);
            exit;
        }
        
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo json_encode(['success' => false, 'error' => 'Subscription plan not found.']);
            exit;
        }
        
        require_once '../vendor/autoload.php';
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
                'success_url' => APP_URL . '/billing.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => APP_URL . '/billing.php?canceled=true',
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
            // Create one-time payment checkout session (for free plans or legacy plans)
            $checkout_session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $plan['name'],
                            'description' => $plan['features'],
                        ],
                        'unit_amount' => $plan['price'] * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => APP_URL . '/billing.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => APP_URL . '/billing.php?canceled=true',
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                ],
            ]);
        }
        
        echo json_encode(['success' => true, 'session_id' => $checkout_session->id]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    }
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    ob_clean(); // Clear any output
    echo json_encode(['success' => false, 'error' => 'Stripe API error: ' . $e->getMessage()]);
} catch (Exception $e) {
    ob_clean(); // Clear any output
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}

ob_end_flush();
?>

