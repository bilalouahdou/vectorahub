<?php
// Test User Management API
// Verify that the fixed users.php works correctly

require_once 'php/config.php';

echo "🔍 User Management API Test\n";
echo "===========================\n\n";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful\n\n";

    // Test the exact query from users.php
    echo "📋 Testing users query...\n";
    
    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            u.role, 
            COALESCE(sp.name, 'N/A') AS current_plan_name,
            COALESCE(sp.coin_limit, 0) AS current_plan_coin_limit,
            COUNT(ij.id) AS total_jobs
        FROM users u 
        LEFT JOIN user_subscriptions us ON u.id = us.user_id AND us.active = TRUE
        LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
        LEFT JOIN image_jobs ij ON u.id = ij.user_id
        GROUP BY u.id, u.full_name, u.email, u.role, sp.name, sp.coin_limit
        ORDER BY u.id DESC 
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully\n";
    echo "📊 Found " . count($users) . " users:\n\n";
    
    foreach ($users as $user) {
        echo "   👤 ID: {$user['id']}\n";
        echo "      Name: {$user['full_name']}\n";
        echo "      Email: {$user['email']}\n";
        echo "      Role: {$user['role']}\n";
        echo "      Plan: {$user['current_plan_name']}\n";
        echo "      Coins: {$user['current_plan_coin_limit']}\n";
        echo "      Jobs: {$user['total_jobs']}\n";
        echo "      ---\n";
    }

    // Test count query
    echo "\n📊 Testing count query...\n";
    $countQuery = "SELECT COUNT(*) FROM users";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();
    echo "✅ Total users: $totalUsers\n";

    echo "\n🎉 User Management API test completed successfully!\n";
    echo "The admin panel should now work correctly.\n";

} catch (Exception $e) {
    echo "❌ User Management API test failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 