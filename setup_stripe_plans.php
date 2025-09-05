<?php
// Setup Stripe Plans Script
// Run this to update your database with Stripe-based subscription plans

require_once 'php/config.php';
require_once 'php/utils.php';

echo "🚀 Setting up Stripe-based subscription plans...\n";

try {
    $pdo = connectDB();
    echo "✅ Database connection successful\n";

    // Add new columns if they don't exist
    echo "📝 Adding new columns...\n";
    
    $pdo->exec("ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS billing_period VARCHAR(20) DEFAULT 'monthly'");
    $pdo->exec("ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(100)");
    $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS stripe_subscription_id VARCHAR(100)");
    $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    
    echo "✅ Columns added successfully\n";

    // Update existing plans instead of deleting them
    echo "📝 Updating existing plans...\n";
    
    // First, check if we have existing plans
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscription_plans WHERE price > 0");
    $existingPlans = $stmt->fetchColumn();
    
    if ($existingPlans > 0) {
        echo "📝 Found $existingPlans existing paid plans, updating them...\n";
        // Instead of deleting, we'll update or insert
    } else {
        echo "📝 No existing paid plans found, inserting new ones...\n";
    }
    
    // Insert your Stripe-based plans
    echo "📝 Inserting Stripe-based plans...\n";
    
    $plans = [
        // Free plan
        ['Free', 0.00, 200, 'monthly', null, '200 free coins/month; basic vectorize only; no bulk; community support'],
        
        // VectraHub Black Pack
        ['VectraHub Black Pack', 5.00, 1000, 'monthly', 'price_1RtT9cJYJk34NKovi3qBfwh4', 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support'],
        ['VectraHub Black Pack - Yearly', 48.00, 12000, 'yearly', 'price_1RtT9vJYJk34NKov8WwgiYGJ', 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support; 20% yearly discount'],
        
        // VectraHub Pro
        ['VectraHub Pro', 9.99, 2000, 'monthly', 'price_1RtAUjJYJk34NKov2o5zue7b', 'Everything in Black Pack + Advanced features; Premium support; HD output'],
        ['VectraHub Pro - Yearly', 95.90, 24000, 'yearly', 'price_1RtAVoJYJk34NKovE86WXQG6', 'Everything in Black Pack + Advanced features; Premium support; HD output; 20% yearly discount'],
        
        // VectraHub API Pro
        ['VectraHub API Pro', 15.00, 1000, 'monthly', 'price_1RtAXiJYJk34NKovNS5cpLFf', '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit'],
        ['VectraHub API Pro - Yearly', 144.00, 12000, 'yearly', 'price_1RtAXyJYJk34NKovkjntSOr7', '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit; 20% yearly discount']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO subscription_plans (name, price, coin_limit, billing_period, stripe_price_id, features) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (name) DO UPDATE SET
            price = EXCLUDED.price,
            coin_limit = EXCLUDED.coin_limit,
            billing_period = EXCLUDED.billing_period,
            stripe_price_id = EXCLUDED.stripe_price_id,
            features = EXCLUDED.features
    ");

    foreach ($plans as $plan) {
        $stmt->execute($plan);
        echo "   ✅ Added/Updated plan: {$plan[0]} - {$plan[3]} ({$plan[1]} USD)\n";
    }

    // Show all plans
    echo "\n📊 Current subscription plans:\n";
    $stmt = $pdo->query("SELECT id, name, price, billing_period, stripe_price_id FROM subscription_plans ORDER BY name, billing_period");
    $allPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPlans as $plan) {
        echo "   {$plan['id']}: {$plan['name']} - {$plan['billing_period']} - \${$plan['price']} - {$plan['stripe_price_id']}\n";
    }

    echo "\n🎉 Setup completed successfully!\n";
    echo "\n📋 Next steps:\n";
    echo "1. Test your billing page at: " . APP_URL . "/billing.php\n";
    echo "2. Configure your Stripe webhook endpoint: " . APP_URL . "/php/stripe_webhook.php\n";
    echo "3. Set up the following webhook events in Stripe:\n";
    echo "   - checkout.session.completed\n";
    echo "   - customer.subscription.created\n";
    echo "   - customer.subscription.updated\n";
    echo "   - customer.subscription.deleted\n";
    echo "   - invoice.payment_succeeded\n";
    echo "   - invoice.payment_failed\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
