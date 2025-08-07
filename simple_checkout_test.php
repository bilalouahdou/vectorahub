<?php
// Minimal checkout test - step by step
header('Content-Type: application/json');

$result = [];

try {
    $result['step_1'] = 'Starting checkout test';
    
    // Step 1: Basic PHP
    require_once 'php/config.php';
    $result['step_2'] = 'Config loaded';
    
    // Step 2: Utils
    require_once 'php/utils.php';
    $result['step_3'] = 'Utils loaded';
    
    // Step 3: Session
    startSession();
    $result['step_4'] = 'Session started';
    
    // Step 4: Database
    $pdo = connectDB();
    $result['step_5'] = 'Database connected';
    
    // Step 5: Stripe vendor
    require_once 'vendor/autoload.php';
    $result['step_6'] = 'Vendor loaded';
    
    // Step 6: Stripe class
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    $result['step_7'] = 'Stripe initialized';
    
    // Step 7: Create a test session
    $checkout_session = \Stripe\Checkout\Session::create([
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Test Product',
                ],
                'unit_amount' => 1000, // $10.00
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://vectrahub.online/success',
        'cancel_url' => 'https://vectrahub.online/cancel',
    ]);
    
    $result['step_8'] = 'Test Stripe session created';
    $result['session_id'] = $checkout_session->id;
    $result['success'] = true;
    
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    $result['file'] = $e->getFile();
    $result['line'] = $e->getLine();
    $result['success'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
