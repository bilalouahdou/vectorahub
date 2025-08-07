<?php
// Final clean checkout session creator
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // Get data
    $planId = $_POST['plan_id'] ?? null;
    $userId = $_SESSION['user_id'];

    if (!$planId) {
        ob_clean();
        echo json_encode(['error' => 'Missing plan ID']);
        exit;
    }

    // Include dependencies
    require_once __DIR__ . '/../config.php';

    // Get plan from database
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        ob_clean();
        echo json_encode(['error' => 'Plan not found']);
        exit;
    }

    // Initialize Stripe
    require_once __DIR__ . '/../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Create checkout session
    $sessionData = [
        'success_url' => 'https://vectrahub.online/billing?success=true',
        'cancel_url' => 'https://vectrahub.online/billing?canceled=true',
        'metadata' => [
            'user_id' => $userId,
            'plan_id' => $planId,
        ],
    ];

    if (!empty($plan['stripe_price_id']) && $plan['price'] > 0) {
        // Subscription
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
        // One-time payment for free plans
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

    $checkout_session = \Stripe\Checkout\Session::create($sessionData);

    // Clear any unexpected output
    ob_clean();

    echo json_encode([
        'success' => true,
        'session_id' => $checkout_session->id
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    ob_clean();
    echo json_encode(['error' => 'Payment system error']);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Server error']);
}

ob_end_flush();
?>

