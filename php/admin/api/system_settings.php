<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

$pdo = connectDB();

try {
    // Check if settings table exists, create if not
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default settings
        $defaultSettings = [
            ['site_name', 'VectorizeAI'],
            ['site_description', 'Professional Image Vectorization Service'],
            ['default_coins', '10'],
            ['maintenance_mode', '0'],
            ['smtp_host', ''],
            ['smtp_port', '587'],
            ['smtp_user', ''],
            ['stripe_api_key', ''],
            ['paypal_client_id', ''],
            ['python_api_url', 'http://localhost:5000'],
            ['python_api_key', ''],
            ['login_attempts_limit', '5'],
            ['session_timeout', '30']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }
    }
    
    // Get all settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settingsArray = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'success' => true,
        'settings' => $settingsArray
    ]);
    
} catch (Exception $e) {
    error_log("System settings error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load system settings']);
}
?>
