<?php
require_once 'utils.php';

// Verify webhook signature
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    require_once '../vendor/autoload.php';
    \Stripe\Stripe::setApiKey($STRIPE_SECRET_KEY);
    
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event['type']) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];
        
        if ($session['payment_status'] === 'paid') {
            $userId = $session['metadata']['user_id'];
            $planId = $session['metadata']['plan_id'];
            $amount = $session['amount_total'] / 100; // Convert from cents
            
            // Process the purchase
            $success = processPurchase($userId, $planId, $amount, $session['id']);
            
            if (!$success) {
                error_log("Failed to process purchase for session: " . $session['id']);
            }
        }
        break;
        
    default:
        error_log('Received unknown event type: ' . $event['type']);
}

http_response_code(200);
?>
