<?php
require_once '../config.php';
require_once '../utils.php';

header('Content-Type: application/json');

startSession(); // Ensure session is started for user_id and csrf_token

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
        $pdo = getDBConnection();
        $pdo->beginTransaction();

        // 1. Get the 'Free' plan details
        $stmt = $pdo->prepare("SELECT id, name, coin_limit FROM subscription_plans WHERE id = ? AND price = 0.00 LIMIT 1");
        $stmt->execute([$planId]);
        $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$freePlan) {
            jsonResponse(['success' => false, 'error' => 'Free plan not found or invalid plan ID.'], 404);
        }

        // 2. Deactivate any existing active subscriptions for this user
        // This ensures a user doesn't have multiple active subscriptions.
        $stmt = $pdo->prepare("UPDATE user_subscriptions SET active = FALSE, end_date = NOW() WHERE user_id = ? AND active = TRUE");
        $stmt->execute([$userId]);

        // 3. Insert or update user subscription to the 'Free' plan
        // We'll set it for 1 year from now.
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, active, is_free_from_coupon)
            VALUES (?, ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR), TRUE, TRUE)
            ON CONFLICT (user_id) DO UPDATE SET
                plan_id = EXCLUDED.plan_id,
                start_date = EXCLUDED.start_date,
                end_date = EXCLUDED.end_date,
                active = EXCLUDED.active,
                is_free_from_coupon = EXCLUDED.is_free_from_coupon;
        ");
        $stmt->execute([$userId, $freePlan['id']]);

        // 4. Update user's coins to the free plan's coin limit
        $stmt = $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?");
        $stmt->execute([$freePlan['coin_limit'], $userId]);

        // 5. Log the activation activity
        logActivity('FREE_PLAN_ACTIVATED', "User $userId activated the '{$freePlan['name']}' plan.", $userId);

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Free plan activated successfully!']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Free plan activation error for user $userId: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'An unexpected error occurred during activation.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
}
?>
