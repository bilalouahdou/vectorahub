<?php
require_once '../config.php';
require_once '../utils.php';

header('Content-Type: application/json');

startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'CSRF token validation failed.'], 403);
    }

    $planId = $_POST['plan_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$planId || !$userId) {
        jsonResponse(['success' => false, 'error' => 'Missing plan ID or user ID.'], 400);
    }

    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            jsonResponse(['success' => false, 'error' => 'Subscription plan not found.'], 404);
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

        jsonResponse(['success' => true, 'session_id' => $checkout_session->id]);

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe API Error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Stripe API error: ' . $e->getMessage()], 500);
    } catch (Exception $e) {
        error_log("Create Checkout Session Error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'An unexpected error occurred.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}
?>
