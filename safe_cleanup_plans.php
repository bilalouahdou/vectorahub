<?php
// Safe Cleanup Plans Script - Handles Foreign Key Constraints
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

    // Check which plans have users subscribed
    echo "\n🔍 Checking user subscriptions...\n";
    $stmt = $pdo->query("
        SELECT sp.id, sp.name, COUNT(us.id) as user_count 
        FROM subscription_plans sp 
        LEFT JOIN user_subscriptions us ON sp.id = us.plan_id 
        WHERE sp.stripe_price_id IS NULL AND sp.name != 'Free'
        GROUP BY sp.id, sp.name
    ");
    $plansWithUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($plansWithUsers as $plan) {
        echo "   Plan '{$plan['name']}' (ID: {$plan['id']}) has {$plan['user_count']} users subscribed\n";
    }

    // Start transaction for safe cleanup
    $pdo->beginTransaction();

    // Step 1: Map users from old plans to new plans with Stripe IDs
    echo "\n🔄 Migrating user subscriptions to new plans...\n";
    
    $migrations = [
        // Old plan name => New plan name mapping
        'Pro' => 'VectraHub Pro',
        'API Pro' => 'VectraHub API Pro', 
        'Black Pack' => 'VectraHub Black Pack'
    ];

    foreach ($migrations as $oldName => $newName) {
        // Get old plan ID
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = ? AND stripe_price_id IS NULL");
        $stmt->execute([$oldName]);
        $oldPlan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get new plan ID (monthly version)
        $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = ? AND billing_period = 'monthly' AND stripe_price_id IS NOT NULL");
        $stmt->execute([$newName]);
        $newPlan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oldPlan && $newPlan) {
            // Update user subscriptions
            $stmt = $pdo->prepare("UPDATE user_subscriptions SET plan_id = ? WHERE plan_id = ?");
            $updated = $stmt->execute([$newPlan['id'], $oldPlan['id']]);
            $count = $stmt->rowCount();
            echo "   ✅ Migrated {$count} users from '{$oldName}' to '{$newName}'\n";
        }
    }

    // Step 2: Now safely delete old plans without Stripe IDs (except Free)
    echo "\n🗑️ Removing old plans without Stripe IDs...\n";
    $stmt = $pdo->prepare("DELETE FROM subscription_plans WHERE stripe_price_id IS NULL AND name != 'Free'");
    $deleted = $stmt->execute();
    $deletedCount = $stmt->rowCount();
    echo "✅ Deleted {$deletedCount} old plans\n";

    // Step 3: Update Free plan to ensure it has proper billing_period
    echo "\n🔄 Updating Free plan...\n";
    $stmt = $pdo->prepare("UPDATE subscription_plans SET billing_period = 'monthly' WHERE name = 'Free'");
    $stmt->execute();
    echo "✅ Updated Free plan\n";

    $pdo->commit();

    // Show final plans
    echo "\n📊 Final plans after cleanup:\n";
    $stmt = $pdo->query("SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY name, billing_period");
    $finalPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalPlans as $plan) {
        $stripeId = $plan['stripe_price_id'] ? $plan['stripe_price_id'] : 'NO STRIPE ID';
        echo "   {$plan['id']}: {$plan['name']} - {$plan['billing_period']} - \${$plan['price']} - {$stripeId}\n";
    }

    echo "\n🎉 Safe cleanup completed successfully!\n";
    echo "\n📋 Summary:\n";
    echo "- Migrated user subscriptions to new plans with Stripe IDs\n";
    echo "- Removed old duplicate plans\n";
    echo "- Preserved Free plan\n";
    echo "- All users should now have valid subscriptions\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

