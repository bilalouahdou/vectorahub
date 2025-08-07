<?php
// Fixed Setup Stripe Plans Script
// This version handles existing data properly

require_once 'php/config.php';
require_once 'php/utils.php';

echo "🚀 Setting up Stripe-based subscription plans (Fixed Version)...\n";

try {
    $pdo = connectDB();
    echo "✅ Database connection successful\n";

    // Add new columns if they don't exist
    echo "📝 Adding new columns...\n";
    
    try {
        $pdo->exec("ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS billing_period VARCHAR(20) DEFAULT 'monthly'");
        $pdo->exec("ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(100)");
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS stripe_subscription_id VARCHAR(100)");
        $pdo->exec("ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Columns added successfully\n";
    } catch (Exception $e) {
        echo "ℹ️  Columns may already exist: " . $e->getMessage() . "\n";
    }

    // Update existing plans and add new ones
    echo "📝 Setting up Stripe-based plans...\n";
    
    $plans = [
        // Free plan - update existing
        ['Free', 0.00, 200, 'monthly', null, '200 free coins/month; basic vectorize only; no bulk; community support'],
        
        // VectraHub Black Pack - new plans
        ['VectraHub Black Pack', 5.00, 1000, 'monthly', 'price_1RtAWsJYJk34NKovlISJ4eNo', 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support'],
        ['VectraHub Black Pack - Yearly', 48.00, 12000, 'yearly', 'price_1RtAXGJYJk34NKovhAPj2DS7', 'Unlimited single-image vectorize; Bulk vectorize; Priority queue; Email support; 20% yearly discount'],
        
        // VectraHub Pro - new plans  
        ['VectraHub Pro', 9.99, 2000, 'monthly', 'price_1RtAUjJYJk34NKov2o5zue7b', 'Everything in Black Pack + Advanced features; Premium support; HD output'],
        ['VectraHub Pro - Yearly', 95.90, 24000, 'yearly', 'price_1RtAVoJYJk34NKovE86WXQG6', 'Everything in Black Pack + Advanced features; Premium support; HD output; 20% yearly discount'],
        
        // VectraHub API Pro - new plans
        ['VectraHub API Pro', 15.00, 1000, 'monthly', 'price_1RtAXiJYJk34NKovNS5cpLFf', '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit'],
        ['VectraHub API Pro - Yearly', 144.00, 12000, 'yearly', 'price_1RtAXyJYJk34NKovkjntSOr7', '1000 API calls/month; JSON responses for titles/keywords; Access to API docs; No daily rate limit; 20% yearly discount']
    ];

    // Use INSERT ... ON CONFLICT to handle duplicates
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
        try {
            $stmt->execute($plan);
            echo "   ✅ Added/Updated plan: {$plan[0]} - {$plan[3]} (\${$plan[1]})\n";
        } catch (Exception $e) {
            echo "   ⚠️  Issue with plan {$plan[0]}: " . $e->getMessage() . "\n";
            
            // If ON CONFLICT doesn't work, try simple INSERT
            try {
                $insertStmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, coin_limit, billing_period, stripe_price_id, features) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->execute($plan);
                echo "   ✅ Inserted plan: {$plan[0]}\n";
            } catch (Exception $e2) {
                echo "   ❌ Failed to insert plan {$plan[0]}: " . $e2->getMessage() . "\n";
            }
        }
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
    echo "1. Test your billing page at: https://vectrahub.fly.dev/billing\n";
    echo "2. Configure your Stripe webhook endpoint: https://vectrahub.fly.dev/php/stripe_webhook.php\n";
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

