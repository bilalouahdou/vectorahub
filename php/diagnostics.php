<?php
// Prevent any HTML output
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clear any previous output
if (ob_get_length()) ob_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
    'https' => isset($_SERVER['HTTPS']) ? 'Yes' : 'No',
    'files_exist' => [],
    'database_connection' => 'Not tested',
    'permissions' => [],
    'php_errors' => []
];

// Check if critical files exist
$critical_files = [
    'config.php',
    'dashboard_stats.php',
    'history.php',
    'upload_handler.php'
];

foreach ($critical_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    $diagnostics['files_exist'][$file] = [
        'exists' => file_exists($file_path),
        'readable' => file_exists($file_path) && is_readable($file_path),
        'path' => $file_path
    ];
}

// Check directory permissions
$directories = [
    'php' => __DIR__,
    'uploads' => dirname(__DIR__) . '/uploads',
    'outputs' => dirname(__DIR__) . '/outputs'
];

foreach ($directories as $name => $dir) {
    $diagnostics['permissions'][$name] = [
        'exists' => is_dir($dir),
        'readable' => is_dir($dir) && is_readable($dir),
        'writable' => is_dir($dir) && is_writable($dir),
        'path' => $dir
    ];
}

// Test database connection if config exists
if (file_exists(__DIR__ . '/config.php')) {
    try {
        require_once 'config.php';
        
        // Check if constants are defined
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $diagnostics['database_connection'] = 'Success';
            $diagnostics['database_constants'] = 'Defined';
            
            // Test if tables exist
            $tables = ['users', 'jobs', 'payments'];
            $diagnostics['database_tables'] = [];
            
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table` LIMIT 1");
                    $diagnostics['database_tables'][$table] = 'Exists';
                } catch (Exception $e) {
                    $diagnostics['database_tables'][$table] = 'Missing or Error: ' . $e->getMessage();
                }
            }
        } else {
            $diagnostics['database_connection'] = 'Config constants not defined';
            $diagnostics['database_constants'] = 'Missing: ' . 
                (!defined('DB_HOST') ? 'DB_HOST ' : '') .
                (!defined('DB_NAME') ? 'DB_NAME ' : '') .
                (!defined('DB_USER') ? 'DB_USER ' : '') .
                (!defined('DB_PASS') ? 'DB_PASS ' : '');
        }
    } catch (Exception $e) {
        $diagnostics['database_connection'] = 'Failed: ' . $e->getMessage();
    }
} else {
    $diagnostics['database_connection'] = 'Config file not found';
}

// Capture any PHP errors
$diagnostics['php_errors'] = error_get_last();

// Test session functionality
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$diagnostics['session'] = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_save_path' => session_save_path()
];

// Ensure clean JSON output
ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
?>
