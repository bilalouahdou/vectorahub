<?php
// Cleanup Duplicate Plans Script
require_once 'php/config.php';
require_once 'php/utils.php';

echo "🧹 Cleaning up duplicate subscription plans...\n";

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

    // Delete old plans that don't have Stripe price IDs (except Free plan)
    echo "\n🗑️ Removing duplicate plans without Stripe IDs...\n";
    
    $stmt = $pdo->prepare("DELETE FROM subscription_plans WHERE stripe_price_id IS NULL AND name != 'Free'");
    $deletedCount = $stmt->execute() ? $stmt->rowCount() : 0;
    
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

