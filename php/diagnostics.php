<?php
require_once 'config.php';

// Test database connection and table structure
try {
    $pdo = getDBConnection();
    echo "Database connection: SUCCESS\n";
    
    // Check if tables exist
    $tables = ['users', 'subscription_plans', 'user_subscriptions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
        $exists = $stmt->fetchColumn();
        echo "Table '$table' exists: " . ($exists ? 'YES' : 'NO') . "\n";
    }
    
    // Check subscription plans
    $stmt = $pdo->query("SELECT id, name FROM subscription_plans");
    $plans = $stmt->fetchAll();
    echo "Available subscription plans:\n";
    foreach ($plans as $plan) {
        echo "- ID: {$plan['id']}, Name: {$plan['name']}\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
