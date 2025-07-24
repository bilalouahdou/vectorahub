<?php
require_once '../../utils.php';
redirectIfNotAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Clear various cache directories
    $cacheDirectories = [
        '../../../uploads/cache/',
        '../../../temp/',
        '../../../cache/'
    ];
    
    $clearedFiles = 0;
    
    foreach ($cacheDirectories as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $clearedFiles++;
                }
            }
        }
    }
    
    // Clear PHP opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Cache cleared successfully. Removed $clearedFiles files."
    ]);
    
} catch (Exception $e) {
    error_log("Clear cache error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to clear cache']);
}
?>
