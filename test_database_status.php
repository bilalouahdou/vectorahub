<?php
// Test Database Status
// Check which tables exist and their current data

require_once 'php/config.php';

echo "🔍 Database Status Check\n";
echo "=======================\n\n";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n\n";

    // List all tables
    echo "📋 Checking tables...\n";
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "   $table: $count records\n";
    }

    echo "\n📊 Detailed Status:\n";
    echo "==================\n";

    // Check users table
    echo "\n👥 Users Table:\n";
    $stmt = $pdo->query("SELECT id, full_name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)) {
        echo "   ❌ No users found\n";
    } else {
        foreach ($users as $user) {
            echo "   ✅ ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}, Role: {$user['role']}\n";
        }
    }

    // Check subscription_plans table
    echo "\n📦 Subscription Plans Table:\n";
    $stmt = $pdo->query("SELECT id, name, price, coin_limit FROM subscription_plans ORDER BY price");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($plans)) {
        echo "   ❌ No subscription plans found\n";
    } else {
        foreach ($plans as $plan) {
            echo "   ✅ ID: {$plan['id']}, Name: {$plan['name']}, Price: \${$plan['price']}, Coins: {$plan['coin_limit']}\n";
        }
    }

    // Check system_settings table
    echo "\n⚙️ System Settings Table:\n";
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($settings)) {
        echo "   ❌ No system settings found\n";
    } else {
        foreach ($settings as $setting) {
            echo "   ✅ {$setting['setting_key']}: {$setting['setting_value']}\n";
        }
    }

    // Check system_logs table
    echo "\n📝 System Logs Table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
    $logsCount = $stmt->fetchColumn();
    echo "   Total logs: $logsCount\n";

    // Check image_jobs table
    echo "\n🖼️ Image Jobs Table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM image_jobs");
    $jobsCount = $stmt->fetchColumn();
    echo "   Total jobs: $jobsCount\n";

    // Check user_subscriptions table
    echo "\n💳 User Subscriptions Table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_subscriptions");
    $subsCount = $stmt->fetchColumn();
    echo "   Total subscriptions: $subsCount\n";

    echo "\n🎯 Recommendations:\n";
    echo "==================\n";
    
    if (empty($users)) {
        echo "   🔴 Need to create users (run init_supabase_data.php)\n";
    }
    
    if (empty($plans)) {
        echo "   🔴 Need to create subscription plans (run init_supabase_data.php)\n";
    }
    
    if (empty($settings)) {
        echo "   🔴 Need to create system settings (run init_supabase_data.php)\n";
    }
    
    if (empty($users) && empty($plans) && empty($settings)) {
        echo "   🟡 Database is empty - run initialization script\n";
    } else {
        echo "   🟢 Database has some data - admin panel should work\n";
    }

} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 