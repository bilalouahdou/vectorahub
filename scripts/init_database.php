<?php
// Database initialization script for Supabase
require_once __DIR__ . '/../php/config.php';

function logMessage($message) {
    echo date('Y-m-d H:i:s') . " - " . $message . "\n";
    error_log($message);
}

function createTables($pdo) {
    $tables = [
        // Users table
        "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            api_key VARCHAR(100) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Subscription plans table
        "CREATE TABLE IF NOT EXISTS subscription_plans (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            coin_limit INTEGER NOT NULL DEFAULT 0,
            features TEXT,
            active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // User subscriptions table
        "CREATE TABLE IF NOT EXISTS user_subscriptions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            plan_id INTEGER REFERENCES subscription_plans(id),
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Coin usage table
        "CREATE TABLE IF NOT EXISTS coin_usage (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            coins_used INTEGER NOT NULL DEFAULT 1,
            operation_type VARCHAR(50) DEFAULT 'vectorize',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Payments table
        "CREATE TABLE IF NOT EXISTS payments (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            amount DECIMAL(10,2) NOT NULL,
            plan_id INTEGER REFERENCES subscription_plans(id),
            payment_method VARCHAR(50) DEFAULT 'stripe',
            transaction_id VARCHAR(255),
            billing_type VARCHAR(20) DEFAULT 'monthly',
            status VARCHAR(50) DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Image jobs table
        "CREATE TABLE IF NOT EXISTS image_jobs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            original_image_path VARCHAR(255),
            original_filename VARCHAR(255),
            output_svg_path VARCHAR(255),
            status VARCHAR(15) NOT NULL DEFAULT 'queued' CHECK (status IN ('queued','processing','done','failed')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_bulk BOOLEAN DEFAULT FALSE,
            bulk_group_id VARCHAR(50),
            bulk_position INTEGER
        )",
        
        // Activity logs table
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id SERIAL PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_id INTEGER REFERENCES users(id),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // System settings table
        "CREATE TABLE IF NOT EXISTS system_settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            logMessage("Table created successfully");
        } catch (PDOException $e) {
            logMessage("Error creating table: " . $e->getMessage());
            throw $e;
        }
    }
}

try {
    logMessage("Starting database initialization...");
    
    // Test connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Could not connect to database");
    }
    
    logMessage("Database connection successful");
    
    // Create tables
    createTables($pdo);
    logMessage("Tables created successfully");
    
    // Read the SQL schema file
    $sqlSchema = file_get_contents(__DIR__ . '/database_schema.sql');

    if ($sqlSchema === false) {
        throw new Exception("Failed to read database_schema.sql");
    }

    // Execute the SQL schema
    // PDO::exec() is suitable for executing multiple SQL statements
    $pdo->exec($sqlSchema);
    logMessage("Database schema created/updated successfully.");
    
    // Insert default subscription plans if they don't exist
    $plans = [
        ['name' => 'Free', 'price' => 0.00, 'coin_limit' => 5, 'features' => '5 image vectorizations per month'],
        ['name' => 'Basic', 'price' => 9.99, 'coin_limit' => 100, 'features' => '100 image vectorizations per month, priority support'],
        ['name' => 'Pro', 'price' => 29.99, 'coin_limit' => 500, 'features' => '500 image vectorizations per month, premium support, API access'],
    ];

    foreach ($plans as $plan) {
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, coin_limit, features) VALUES (:name, :price, :coin_limit, :features) ON CONFLICT (name) DO NOTHING");
        $stmt->execute([
            ':name' => $plan['name'],
            ':price' => $plan['price'],
            ':coin_limit' => $plan['coin_limit'],
            ':features' => $plan['features']
        ]);
    }
    logMessage("Default subscription plans inserted/updated successfully.");
    
    // Insert default system settings if they don't exist
    $settings = [
        ['setting_key' => 'app_name', 'setting_value' => APP_NAME],
        ['setting_key' => 'app_url', 'setting_value' => APP_URL],
        ['setting_key' => 'upload_max_size', 'setting_value' => UPLOAD_MAX_SIZE],
        ['setting_key' => 'session_lifetime', 'setting_value' => SESSION_LIFETIME],
        ['setting_key' => 'csrf_token_expiry', 'setting_value' => CSRF_TOKEN_EXPIRY],
        ['setting_key' => 'default_free_plan_id', 'setting_value' => '1'], // Assuming 'Free' plan has ID 1
    ];

    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()");
        $stmt->execute([
            ':key' => $setting['setting_key'],
            ':value' => $setting['setting_value']
        ]);
    }
    logMessage("Default system settings inserted/updated successfully.");
    
    logMessage("Database initialization completed successfully");
    
} catch (Exception $e) {
    logMessage("Database initialization failed: " . $e->getMessage());
    exit(1);
}
?>
