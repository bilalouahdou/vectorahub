<?php
require_once 'utils.php';
require_once 'config.php'; // Ensure config is loaded for Stripe keys

// Verify webhook signature
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    require_once '../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, STRIPE_WEBHOOK_SECRET
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
        
    case 'customer.subscription.created':
        // Handle new subscription
        $subscription = $event['data']['object'];
        $userId = $subscription['metadata']['user_id'] ?? null;
        $planId = $subscription['metadata']['plan_id'] ?? null;
        
        if ($userId && $planId) {
            $success = processSubscriptionCreated($userId, $planId, $subscription['id'], $subscription);
            if (!$success) {
                error_log("Failed to process subscription creation: " . $subscription['id']);
            }
        }
        break;
        
    case 'customer.subscription.updated':
        // Handle subscription updates (e.g., plan changes)
        $subscription = $event['data']['object'];
        $success = processSubscriptionUpdated($subscription['id'], $subscription);
        if (!$success) {
            error_log("Failed to process subscription update: " . $subscription['id']);
        }
        break;
        
    case 'customer.subscription.deleted':
        // Handle subscription cancellation
        $subscription = $event['data']['object'];
        $success = processSubscriptionCanceled($subscription['id']);
        if (!$success) {
            error_log("Failed to process subscription cancellation: " . $subscription['id']);
        }
        break;
        
    case 'invoice.payment_succeeded':
        // Handle successful recurring payments
        $invoice = $event['data']['object'];
        if ($invoice['subscription']) {
            $success = processRecurringPayment($invoice['subscription'], $invoice);
            if (!$success) {
                error_log("Failed to process recurring payment: " . $invoice['id']);
            }
        }
        break;
        
    case 'invoice.payment_failed':
        // Handle failed recurring payments
        $invoice = $event['data']['object'];
        if ($invoice['subscription']) {
            $success = processFailedPayment($invoice['subscription'], $invoice);
            if (!$success) {
                error_log("Failed to process failed payment: " . $invoice['id']);
            }
        }
        break;
        
    default:
        error_log('Received unknown event type: ' . $event['type']);
}

http_response_code(200);
?>
