<?php
// Initialize Supabase Database with Basic Data
// Run this script to set up your Supabase database with required data

require_once 'php/config.php';

echo "🚀 Initializing Supabase Database with Basic Data...\n";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n";

    // 1. Insert default subscription plans
    echo "\n📝 Inserting default subscription plans...\n";
    
    $plans = [
        ['name' => 'Free', 'price' => 0.00, 'coin_limit' => 10, 'features' => '10 vectorizations per month, Standard processing, Basic support'],
        ['name' => 'Ultimate', 'price' => 5.00, 'coin_limit' => 200, 'features' => '200 vectorizations per month, Priority processing, Email support, HD output'],
        ['name' => 'API Pro', 'price' => 15.00, 'coin_limit' => 1000, 'features' => '1000 vectorizations per month, API access, Priority processing, Premium support, Bulk operations']
    ];

    $stmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES (:name, :price, :coin_limit, :features) ON CONFLICT (name) DO NOTHING");

    foreach ($plans as $plan) {
        $stmt->execute([
            ':name' => $plan['name'],
            ':price' => $plan['price'],
            ':coin_limit' => $plan['coin_limit'],
            ':features' => $plan['features']
        ]);
        echo "   ✅ Added plan: {$plan['name']}\n";
    }

    // 2. Insert default system settings
    echo "\n📝 Inserting default system settings...\n";
    
    $settings = [
        ['setting_key' => 'app_name', 'setting_value' => 'VectraHub'],
        ['setting_key' => 'app_url', 'setting_value' => 'https://vectrahub.fly.dev'],
        ['setting_key' => 'upload_max_size', 'setting_value' => '5242880'],
        ['setting_key' => 'session_lifetime', 'setting_value' => '86400'],
        ['setting_key' => 'csrf_token_expiry', 'setting_value' => '3600'],
        ['setting_key' => 'default_free_plan_id', 'setting_value' => '1']
    ];

    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT (setting_key) DO NOTHING");

    foreach ($settings as $setting) {
        $stmt->execute([
            ':key' => $setting['setting_key'],
            ':value' => $setting['setting_value']
        ]);
        echo "   ✅ Added setting: {$setting['setting_key']}\n";
    }

    // 3. Create a test admin user
    echo "\n📝 Creating test admin user...\n";
    
    $adminEmail = 'admin@vectrahub.com';
    $adminPassword = 'admin123'; // Change this in production!
    $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :password, :role) ON CONFLICT (email) DO NOTHING");
    $stmt->execute([
        ':name' => 'Admin User',
        ':email' => $adminEmail,
        ':password' => $adminPasswordHash,
        ':role' => 'admin'
    ]);
    
    echo "   ✅ Admin user created: $adminEmail (password: $adminPassword)\n";

    // 4. Create a test regular user
    echo "\n📝 Creating test regular user...\n";
    
    $userEmail = 'user@vectrahub.com';
    $userPassword = 'user123'; // Change this in production!
    $userPasswordHash = password_hash($userPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :password, :role) ON CONFLICT (email) DO NOTHING");
    $stmt->execute([
        ':name' => 'Test User',
        ':email' => $userEmail,
        ':password' => $userPasswordHash,
        ':role' => 'user'
    ]);
    
    echo "   ✅ Regular user created: $userEmail (password: $userPassword)\n";

    // 5. Assign free subscription to test user
    echo "\n📝 Assigning free subscription to test user...\n";
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $userId = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free'");
    $stmt->execute();
    $planId = $stmt->fetchColumn();
    
    if ($userId && $planId) {
        $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date) VALUES (:user_id, :plan_id, TRUE, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 month') ON CONFLICT (user_id, plan_id) DO NOTHING");
        $stmt->execute([
            ':user_id' => $userId,
            ':plan_id' => $planId
        ]);
        echo "   ✅ Free subscription assigned to test user\n";
    }

    // 6. Add some sample system logs
    echo "\n📝 Adding sample system logs...\n";
    
    $sampleLogs = [
        ['info', 'System initialized successfully', null, '127.0.0.1'],
        ['info', 'Admin user created', $userId, '127.0.0.1'],
        ['info', 'Test user registered', $userId, '127.0.0.1'],
        ['info', 'Database setup completed', null, '127.0.0.1']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO system_logs (type, description, user_id, ip_address) VALUES (:type, :description, :user_id, :ip)");
    
    foreach ($sampleLogs as $log) {
        $stmt->execute([
            ':type' => $log[0],
            ':description' => $log[1],
            ':user_id' => $log[2],
            ':ip' => $log[3]
        ]);
    }
    
    echo "   ✅ Sample logs added\n";

    // 7. Verify data
    echo "\n📊 Verifying data...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "   Users: $userCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscription_plans");
    $planCount = $stmt->fetchColumn();
    echo "   Subscription Plans: $planCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    $settingsCount = $stmt->fetchColumn();
    echo "   System Settings: $settingsCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
    $logsCount = $stmt->fetchColumn();
    echo "   System Logs: $logsCount\n";

    echo "\n🎉 Database initialization completed successfully!\n";
    echo "Your Supabase database is now ready for the admin panel.\n";
    echo "\n📋 Test Accounts:\n";
    echo "   Admin: admin@vectrahub.com / admin123\n";
    echo "   User: user@vectrahub.com / user123\n";

} catch (Exception $e) {
    echo "❌ Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 