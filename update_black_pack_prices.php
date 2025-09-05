<?php
/**
 * Update VectraHub Black Pack Price IDs
 * 
 * This script updates the stripe_price_id for VectraHub Black Pack plans only
 */

require_once 'php/config.php';
require_once 'php/utils.php';

echo "🔄 Updating VectraHub Black Pack Price IDs...\n";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n";

    // Update monthly Black Pack
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET stripe_price_id = ? 
        WHERE name = 'VectraHub Black Pack' AND billing_period = 'monthly'
    ");
    $stmt->execute(['price_1RtT9cJYJk34NKovi3qBfwh4']);
    $monthlyUpdated = $stmt->rowCount();
    echo "✅ Updated {$monthlyUpdated} monthly Black Pack plan(s)\n";

    // Update yearly Black Pack
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET stripe_price_id = ? 
        WHERE name = 'VectraHub Black Pack - Yearly' AND billing_period = 'yearly'
    ");
    $stmt->execute(['price_1RtT9vJYJk34NKov8WwgiYGJ']);
    $yearlyUpdated = $stmt->rowCount();
    echo "✅ Updated {$yearlyUpdated} yearly Black Pack plan(s)\n";

    // Verify the updates
    echo "\n📊 Current Black Pack plans:\n";
    $stmt = $pdo->prepare("
        SELECT id, name, billing_period, stripe_price_id 
        FROM subscription_plans 
        WHERE name LIKE '%Black Pack%'
        ORDER BY name, billing_period
    ");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($plans as $plan) {
        echo "   {$plan['id']}: {$plan['name']} - {$plan['billing_period']} - {$plan['stripe_price_id']}\n";
    }

    echo "\n🎉 Price ID update completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

