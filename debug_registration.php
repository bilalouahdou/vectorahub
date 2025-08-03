<?php
// Debug script for registration issues
// Run this to diagnose the problem

require_once 'php/config.php';

echo "🔍 Registration Debug Script\n";
echo "==========================\n\n";

try {
    // 1. Test database connection
    echo "1. Testing database connection...\n";
    $pdo = getDBConnection();
    echo "   ✅ Database connection successful\n";
    
    // 2. Check if tables exist
    echo "\n2. Checking table structure...\n";
    $tables = ['users', 'subscription_plans', 'user_subscriptions', 'system_settings'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   ✅ Table '$table' exists with $count rows\n";
        } catch (Exception $e) {
            echo "   ❌ Table '$table' missing or inaccessible: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Check subscription plans
    echo "\n3. Checking subscription plans...\n";
    $stmt = $pdo->prepare("SELECT id, name, price FROM subscription_plans ORDER BY price");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($plans)) {
        echo "   ❌ No subscription plans found!\n";
        echo "   💡 Run the initialization script: php init_supabase.php\n";
    } else {
        echo "   ✅ Found " . count($plans) . " subscription plans:\n";
        foreach ($plans as $plan) {
            echo "      - ID: {$plan['id']}, Name: {$plan['name']}, Price: \${$plan['price']}\n";
        }
    }
    
    // 4. Check if Free plan exists
    echo "\n4. Checking for Free plan...\n";
    $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' OR price = 0 LIMIT 1");
    $stmt->execute();
    $freePlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freePlan) {
        echo "   ✅ Free plan found with ID: {$freePlan['id']}\n";
    } else {
        echo "   ❌ No Free plan found! This will cause registration to fail.\n";
    }
    
    // 5. Test user insertion (without committing)
    echo "\n5. Testing user insertion (dry run)...\n";
    $pdo->beginTransaction();
    
    try {
        $testEmail = 'test_' . time() . '@example.com';
        $testName = 'Test User';
        $testPassword = 'TestPass123!';
        $passwordHash = password_hash($testPassword, PASSWORD_BCRYPT);
        
        // Test user insertion
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, 'user') RETURNING id");
        $stmt->execute([
            ':full_name' => $testName,
            ':email' => $testEmail,
            ':password_hash' => $passwordHash
        ]);
        $userId = $stmt->fetchColumn();
        
        if ($userId) {
            echo "   ✅ User insertion works (ID: $userId)\n";
            
            // Test subscription assignment
            if ($freePlan) {
                $stmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, active, start_date, end_date, is_free_from_coupon) VALUES (:user_id, :plan_id, TRUE, CURRENT_DATE, CURRENT_DATE + INTERVAL '1 year', FALSE)");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':plan_id' => $freePlan['id']
                ]);
                echo "   ✅ Subscription assignment works\n";
            } else {
                echo "   ⚠️  Cannot test subscription assignment (no Free plan)\n";
            }
        } else {
            echo "   ❌ User insertion failed\n";
        }
        
        $pdo->rollBack();
        echo "   ✅ Rollback successful (test data not saved)\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "   ❌ Test failed: " . $e->getMessage() . "\n";
    }
    
    // 6. Check environment variables
    echo "\n6. Checking environment variables...\n";
    $envVars = ['DATABASE_URL', 'SUPABASE_URL', 'SUPABASE_ANON_KEY', 'SUPABASE_SERVICE_ROLE_KEY'];
    
    foreach ($envVars as $var) {
        $value = getenv($var);
        if ($value) {
            echo "   ✅ $var is set\n";
        } else {
            echo "   ❌ $var is missing\n";
        }
    }
    
    // 7. Check PHP extensions
    echo "\n7. Checking PHP extensions...\n";
    $extensions = ['pdo', 'pdo_pgsql', 'pgsql'];
    
    foreach ($extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "   ✅ $ext extension loaded\n";
        } else {
            echo "   ❌ $ext extension missing\n";
        }
    }
    
    echo "\n🎉 Debug completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Debug failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 