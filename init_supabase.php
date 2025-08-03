<?php
// Supabase Database Initialization Script
// Run this script to set up your Supabase database with required data

require_once 'php/config.php';

echo "🚀 Initializing Supabase Database...\n";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n";
    
    // Check if subscription_plans table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscription_plans");
    $planCount = $stmt->fetchColumn();
    
    if ($planCount == 0) {
        echo "📝 Inserting default subscription plans...\n";
        
        // Insert default subscription plans
        $plans = [
            ['name' => 'Free', 'price' => 0.00, 'coin_limit' => 10, 'features' => '10 vectorizations per month, Standard processing, Basic support'],
            ['name' => 'Ultimate', 'price' => 5.00, 'coin_limit' => 200, 'features' => '200 vectorizations per month, Priority processing, Email support, HD output'],
            ['name' => 'API Pro', 'price' => 15.00, 'coin_limit' => 1000, 'features' => '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES (:name, :price, :coin_limit, :features)");
        
        foreach ($plans as $plan) {
            $stmt->execute([
                ':name' => $plan['name'],
                ':price' => $plan['price'],
                ':coin_limit' => $plan['coin_limit'],
                ':features' => $plan['features']
            ]);
            echo "   ✅ Added plan: {$plan['name']}\n";
        }
    } else {
        echo "ℹ️  Subscription plans already exist ({$planCount} plans found)\n";
    }
    
    // Check if system_settings table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    $settingsCount = $stmt->fetchColumn();
    
    if ($settingsCount == 0) {
        echo "📝 Inserting default system settings...\n";
        
        $settings = [
            ['setting_key' => 'app_name', 'setting_value' => 'VectraHub'],
            ['setting_key' => 'app_url', 'setting_value' => 'https://vectrahub.fly.dev'],
            ['setting_key' => 'upload_max_size', 'setting_value' => '5242880'],
            ['setting_key' => 'session_lifetime', 'setting_value' => '86400'],
            ['setting_key' => 'csrf_token_expiry', 'setting_value' => '3600'],
            ['setting_key' => 'default_free_plan_id', 'setting_value' => '1']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)");
        
        foreach ($settings as $setting) {
            $stmt->execute([
                ':key' => $setting['setting_key'],
                ':value' => $setting['setting_value']
            ]);
            echo "   ✅ Added setting: {$setting['setting_key']}\n";
        }
    } else {
        echo "ℹ️  System settings already exist ({$settingsCount} settings found)\n";
    }
    
    // Verify the Free plan exists
    $stmt = $pdo->prepare("SELECT id, name FROM subscription_plans WHERE name = 'Free'");
    $stmt->execute();
    $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freePlan) {
        echo "✅ Free plan found with ID: {$freePlan['id']}\n";
    } else {
        echo "❌ Free plan not found! This will cause registration to fail.\n";
        exit(1);
    }
    
    echo "\n🎉 Database initialization completed successfully!\n";
    echo "Your Supabase database is now ready for user registration.\n";
    
} catch (Exception $e) {
    echo "❌ Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 