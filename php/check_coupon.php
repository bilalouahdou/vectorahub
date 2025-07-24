<?php
require_once 'utils.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['valid' => false, 'message' => 'Invalid request method'], 405);
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonResponse(['valid' => false, 'message' => 'Invalid security token'], 403);
    }

    // Get coupon code from POST data
    $couponCode = isset($_POST['coupon_code']) ? trim(strtoupper($_POST['coupon_code'])) : '';

    if (empty($couponCode)) {
        jsonResponse(['valid' => false, 'message' => 'Please enter a coupon code']);
    }

    // Connect to database
    $pdo = connectDB();
    
    // Check if coupon exists and is valid
    $stmt = $pdo->prepare("
        SELECT c.*, sp.name as plan_name, sp.coin_limit, sp.price as plan_price
        FROM coupon_codes c
        LEFT JOIN subscription_plans sp ON c.free_plan_id = sp.id
        WHERE c.code = ? 
        AND c.valid_from <= CURDATE() 
        AND c.valid_until >= CURDATE()
        AND (c.max_uses IS NULL OR c.current_uses < c.max_uses)
    ");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        jsonResponse(['valid' => false, 'message' => 'Invalid or expired coupon code']);
    }
    
    // Prepare response based on coupon type
    $response = [
        'valid' => true,
        'type' => $coupon['type'],
        'description' => $coupon['description'] ?? ''
    ];
    
    switch ($coupon['type']) {
        case 'discount':
            $response['discount_percent'] = (int)$coupon['discount_percent'];
            $response['discount_amount'] = (float)$coupon['discount_amount'];
            $response['message'] = "Coupon applied! {$coupon['discount_percent']}% discount";
            break;
            
        case 'free_plan':
            $response['free_plan_id'] = (int)$coupon['free_plan_id'];
            $response['free_duration_months'] = (int)$coupon['free_duration_months'];
            $response['plan_name'] = $coupon['plan_name'] ?? 'Premium';
            $response['plan_coins'] = (int)$coupon['coin_limit'];
            $response['message'] = "Free {$coupon['plan_name']} plan for {$coupon['free_duration_months']} month(s)!";
            break;
            
        case 'free_upgrade':
            $response['free_plan_id'] = (int)$coupon['free_plan_id'];
            $response['free_duration_months'] = (int)$coupon['free_duration_months'];
            $response['plan_name'] = $coupon['plan_name'] ?? 'Premium';
            $response['plan_coins'] = (int)$coupon['coin_limit'];
            $response['message'] = "Free upgrade to {$coupon['plan_name']} for {$coupon['free_duration_months']} month(s)!";
            break;
            
        default:
            jsonResponse(['valid' => false, 'message' => 'Invalid coupon type']);
    }
    
    jsonResponse($response);
    
} catch (Exception $e) {
    error_log("Coupon check error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['valid' => false, 'message' => 'System error. Please try again later.'], 500);
}
?>
