<?php
require_once 'utils.php';
redirectIfNotAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => 'Invalid CSRF token'], 400);
}

$planId = intval($_POST['plan_id'] ?? 0);
$billingType = $_POST['type'] ?? 'monthly'; // 'monthly' or 'yearly'

if (!$planId) {
    jsonResponse(['error' => 'Invalid plan ID'], 400);
}

if (!in_array($billingType, ['monthly', 'yearly'])) {
    jsonResponse(['error' => 'Invalid billing type'], 400);
}

try {
    // Get plan details
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        jsonResponse(['error' => 'Plan not found'], 404);
    }
    
    if ($plan['price'] <= 0) {
        jsonResponse(['error' => 'Cannot purchase free plan'], 400);
    }
    
    // Calculate price based on billing type
    if ($billingType === 'yearly') {
        // Apply 20% discount for yearly billing: price * 12 * 0.8
        $finalPrice = $plan['price'] * 12 * 0.8;
        $description = $plan['coin_limit'] . ' coins/month for 12 months (20% off)';
        $interval = 'year';
    } else {
        // Monthly billing
        $finalPrice = $plan['price'];
        $description = $plan['coin_limit'] . ' coins for VectorizeAI';
        $interval = 'month';
    }
    
    // Initialize Stripe
    require_once '../vendor/autoload.php';
    \Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);
    
    // Create checkout session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $plan['name'] . ' Plan (' . ucfirst($billingType) . ')',
                    'description' => $description,
                ],
                'unit_amount' => round($finalPrice * 100), // Convert to cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $APP_URL . '/billing.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $APP_URL . '/pricing.php',
        'customer_email' => $_SESSION['user_email'] ?? null,
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'plan_id' => $planId,
            'billing_type' => $billingType,
            'original_price' => $plan['price'],
            'final_price' => $finalPrice,
        ],
    ]);
    
    jsonResponse(['session_id' => $session->id]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe API error: " . $e->getMessage());
    jsonResponse(['error' => 'Payment service error. Please try again.'], 500);
} catch (Exception $e) {
    error_log("Checkout session creation error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to create checkout session'], 500);
}
?>
