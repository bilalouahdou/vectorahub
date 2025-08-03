<?php
require_once '../../config.php';
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

try {
    $tab = $_POST['tab'] ?? 'general';
    
    // Create settings table if it doesn't exist
    $pdo = connectDB();
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $saved = 0;
    
    // Save settings based on tab
    foreach ($_POST as $key => $value) {
        if ($key === 'tab') continue;
        
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
        $saved++;
    }
    
    // Log the settings update
    $stmt = $pdo->prepare("INSERT INTO system_logs (type, description, user_id, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'info',
        "System settings updated: {$tab} tab ({$saved} settings)",
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Save settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
