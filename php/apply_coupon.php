<?php
require_once 'utils.php';
redirectIfNotAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => 'Invalid CSRF token'], 400);
}

$couponCode = sanitizeInput($_POST['coupon_code'] ?? '');
$userId = $_SESSION['user_id'];

if (empty($couponCode)) {
    jsonResponse(['error' => 'No coupon code provided'], 400);
}

try {
    $pdo = connectDB();
    
    // Get coupon details
    $stmt = $pdo->prepare("
        SELECT c.*, sp.name as plan_name, sp.coin_limit
        FROM coupon_codes c
        LEFT JOIN subscription_plans sp ON c.free_plan_id = sp.id
        WHERE c.code = ? 
        AND c.valid_from <= CURRENT_DATE
AND c.valid_until >= CURRENT_DATE
        AND (c.max_uses IS NULL OR c.current_uses < c.max_uses)
    ");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        jsonResponse(['error' => 'Invalid or expired coupon code'], 400);
    }
    
    // Check if user already used this coupon
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_subscriptions 
        WHERE user_id = ? AND coupon_id = ?
    ");
    $stmt->execute([$userId, $coupon['id']]);
    $alreadyUsed = $stmt->fetchColumn() > 0;
    
    if ($alreadyUsed) {
        jsonResponse(['error' => 'You have already used this coupon'], 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($coupon['type'] === 'free_plan' || $coupon['type'] === 'free_upgrade') {
            // Apply free plan access
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+{$coupon['free_duration_months']} months"));
            
            // Deactivate current subscription if exists
            $stmt = $pdo->prepare("UPDATE user_subscriptions SET active = FALSE WHERE user_id = ? AND active = TRUE");
            $stmt->execute([$userId]);
            
            // Create new subscription with coupon
            $stmt = $pdo->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date, coupon_id, is_free_from_coupon)
                VALUES (?, ?, TRUE, ?, ?, ?, TRUE)
            ");
            $stmt->execute([$userId, $coupon['free_plan_id'], $startDate, $endDate, $coupon['id']]);
            
            // Reset user's coins to the new plan limit
            $stmt = $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?");
            $stmt->execute([$coupon['coin_limit'], $userId]);
            
            $message = "Free {$coupon['plan_name']} plan activated for {$coupon['free_duration_months']} month(s)!";
        }
        
        // Update coupon usage count
        $stmt = $pdo->prepare("UPDATE coupon_codes SET current_uses = current_uses + 1 WHERE id = ?");
        $stmt->execute([$coupon['id']]);
        
        $pdo->commit();
        
        jsonResponse([
            'success' => true,
            'message' => $message ?? 'Coupon applied successfully!',
            'type' => $coupon['type'],
            'redirect' => 'dashboard'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Apply coupon error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to apply coupon'], 500);
}
?>
