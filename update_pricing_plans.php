<?php
require_once 'php/config.php';
require_once 'php/utils.php';

try {
    $pdo = getDBConnection();
    
    echo "Updating pricing plans to match real offers...\n";
    
    // Update Free Plan
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET coin_limit = 25, features = '10 vectorizations per month;Standard processing;Basic support'
        WHERE name = 'Free'
    ");
    $stmt->execute();
    echo "✓ Updated Free plan (25 coins, 10 vectorizations/month)\n";
    
    // Update Black Pack
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET coin_limit = 1000, price = 5.00, features = 'Unlimited black image vectorizations;1,000,000 standard image vectorizations;Priority processing;Email support'
        WHERE name = 'Black Pack'
    ");
    $stmt->execute();
    echo "✓ Updated Black Pack (1000 coins, $5/month)\n";
    
    // Update Pro Plan
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET coin_limit = 500, price = 9.99, features = '200 vectorizations per month;Priority processing;Email support;HD output'
        WHERE name = 'Pro'
    ");
    $stmt->execute();
    echo "✓ Updated Pro plan (500 coins, $9.99/month)\n";
    
    // Update API Pro Plan
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET coin_limit = 5000, price = 15.00, features = '1,000 vectorizations per month;API access;Priority processing;Premium support;Bulk operations'
        WHERE name = 'API Pro'
    ");
    $stmt->execute();
    echo "✓ Updated API Pro plan (5000 API calls, $15/month)\n";
    
    echo "\n✅ All pricing plans updated successfully!\n";
    echo "\nUpdated Plans:\n";
    echo "- Free: 25 coins/month (10 vectorizations)\n";
    echo "- Black Pack: 1000 coins/month ($5/month)\n";
    echo "- Pro: 500 coins/month ($9.99/month) - Most Popular\n";
    echo "- API Pro: 5000 API calls/month ($15/month)\n";
    
} catch (Exception $e) {
    echo "❌ Error updating plans: " . $e->getMessage() . "\n";
}
?> 