<?php
// Ultra-simple working checkout
try {
    // Start session and basic setup
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    
    // Log to file for debugging
    error_log("Checkout request started: " . date('Y-m-d H:i:s'));
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Please log in first']);
        exit;
    }
    
    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST method required']);
        exit;
    }
    
    // Get plan ID
    $planId = $_POST['plan_id'] ?? null;
    if (!$planId) {
        echo json_encode(['error' => 'Plan ID required']);
        exit;
    }
    
    // Get database connection
    require_once __DIR__ . '/../config.php';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo json_encode(['error' => 'Plan not found']);
            exit;
        }
        
        error_log("Plan found: " . $plan['name']);
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    // For now, just return success with plan info
    echo json_encode([
        'success' => true,
        'plan_name' => $plan['name'],
        'plan_price' => $plan['price'],
        'user_id' => $_SESSION['user_id'],
        'message' => 'Checkout test successful - Stripe integration coming next'
    ]);
    
} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>

