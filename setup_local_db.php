<?php
/**
 * Local Database Setup Script
 * 
 * This script helps you set up the local database connection for development.
 * Run this script to test your database connection and create necessary tables.
 */

echo "=== VectraHub Local Database Setup ===\n\n";

// Check if .env file exists
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ .env file not found!\n\n";
    echo "Please create a .env file in the root directory with your Supabase credentials:\n\n";
    echo "DATABASE_URL=postgresql://postgres:[YOUR-PASSWORD]@db.ozjbgmcxocvznfcttaty.supabase.co:5432/postgres\n";
    echo "SUPABASE_URL=https://ozjbgmcxocvznfcttaty.supabase.co\n";
    echo "SUPABASE_ANON_KEY=[YOUR-ANON-KEY]\n";
    echo "SUPABASE_SERVICE_ROLE_KEY=[YOUR-SERVICE-ROLE-KEY]\n";
    echo "APP_ENV=development\n\n";
    echo "You can get these credentials from your Supabase project dashboard.\n";
    exit(1);
}

echo "✅ .env file found\n";

// Load environment variables
require_once 'php/config.php';

// Test database connection
echo "\nTesting database connection...\n";
try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful!\n";
    
    // Test if tables exist
    echo "\nChecking database tables...\n";
    $tables = ['users', 'subscription_plans', 'user_subscriptions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
        $exists = $stmt->fetchColumn();
        echo "- Table '$table': " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
    }
    
    // Check subscription plans
    echo "\nChecking subscription plans...\n";
    $stmt = $pdo->query("SELECT id, name FROM subscription_plans");
    $plans = $stmt->fetchAll();
    
    if (empty($plans)) {
        echo "❌ No subscription plans found. You need to run the database initialization.\n";
        echo "Run: php scripts/init_database.php\n";
    } else {
        echo "✅ Found " . count($plans) . " subscription plans:\n";
        foreach ($plans as $plan) {
            echo "- ID: {$plan['id']}, Name: {$plan['name']}\n";
        }
    }
    
    echo "\n🎉 Setup complete! Your local environment should now work with Supabase.\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    echo "Please check your .env file and make sure:\n";
    echo "1. DATABASE_URL is correct\n";
    echo "2. Your Supabase database is accessible\n";
    echo "3. The password in DATABASE_URL is correct\n";
    exit(1);
}
?> 