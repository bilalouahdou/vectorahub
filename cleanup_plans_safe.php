<?php
// Safe Cleanup Script - Handles Foreign Key Constraints
require_once 'php/config.php';
require_once 'php/utils.php';

echo "🧹 Safely cleaning up subscription plans...\n";

try {
    $pdo = connectDB();
    echo "✅ Database connection successful\n";

    // Show current plans
    echo "\n📊 Current plans before cleanup:\n";
    $stmt = $pdo->query("SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY name, billing_period");
    $allPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPlans as $plan) {
        $stripeId = $plan['stripe_price_id'] ? $plan['stripe_price_id'] : 'NO STRIPE ID';
        echo "   {$plan['id']}: {$plan['name']} - {$plan['billing_period']} - \${$plan['price']} - {$stripeId}\n";
    }

    // First, update any user subscriptions that reference old plans to point to new plans
    echo "\n🔄 Updating user subscriptions to use new plans...\n";
    
    // Update old "Pro" (id=2) to new "VectraHub Pro" (id=11)
    $stmt = $pdo->prepare("UPDATE user_subscriptions SET plan_id = 11 WHERE plan_id = 2");
    $updated1 = $stmt->execute() ? $stmt->rowCount() : 0;
    echo "   ✅ Updated {$updated1} subscriptions from old Pro to VectraHub Pro\n";
    
    // Update old "API Pro" (id=3) to new "VectraHub API Pro" (id=13)
    $stmt = $pdo->prepare("UPDATE user_subscriptions SET plan_id = 13 WHERE plan_id = 3");
    $updated2 = $stmt->execute() ? $stmt->rowCount() : 0;
    echo "   ✅ Updated {$updated2} subscriptions from old API Pro to VectraHub API Pro\n";
    
    // Update old "Black Pack" (id=7) to new "VectraHub Black Pack" (id=9)
    $stmt = $pdo->prepare("UPDATE user_subscriptions SET plan_id = 9 WHERE plan_id = 7");
    $updated3 = $stmt->execute() ? $stmt->rowCount() : 0;
    echo "   ✅ Updated {$updated3} subscriptions from old Black Pack to VectraHub Black Pack\n";

    // Now we can safely delete the old plans (except Free plan)
    echo "\n🗑️ Removing duplicate plans without Stripe IDs...\n";
    
    $oldPlanIds = [2, 3, 7]; // Pro, API Pro, Black Pack (old versions)
    $deletedCount = 0;
    
    foreach ($oldPlanIds as $planId) {
        $stmt = $pdo->prepare("DELETE FROM subscription_plans WHERE id = ?");
        if ($stmt->execute([$planId])) {
            $deletedCount++;
            echo "   ✅ Deleted old plan ID {$planId}\n";
        }
    }
    
    echo "✅ Deleted {$deletedCount} duplicate plans\n";

    // Show final plans
    echo "\n📊 Final plans after cleanup:\n";
    $stmt = $pdo->query("SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY name, billing_period");
    $finalPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalPlans as $plan) {
        $stripeId = $plan['stripe_price_id'] ? $plan['stripe_price_id'] : 'NO STRIPE ID';
        echo "   {$plan['id']}: {$plan['name']} - {$plan['billing_period']} - \${$plan['price']} - {$stripeId}\n";
    }

    echo "\n🎉 Cleanup completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

