<?php
require_once __DIR__ . '/config.php';

// Start session after config is loaded
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Disable HTML error output and force JSON/proper responses
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Function to log debug information
function logDownloadDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] DOWNLOAD: $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

logDownloadDebug("=== DOWNLOAD REQUEST START ===");
logDownloadDebug("GET parameters", $_GET);
logDownloadDebug("Current directory", __DIR__);

// Get the filename from the URL
$filename = isset($_GET['file']) ? trim($_GET['file']) : '';
logDownloadDebug("Requested filename", $filename);

// Security check - only allow SVG files
if (empty($filename)) {
    logDownloadDebug("No filename provided");
    http_response_code(400);
    echo 'Error: No file specified';
    exit;
}

// Ensure it's an SVG file
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($extension !== 'svg') {
    logDownloadDebug("Invalid file extension", $extension);
    http_response_code(400);
    echo 'Error: Only SVG files are allowed';
    exit;
}

// Clean the filename to prevent directory traversal
$filename = basename($filename);
logDownloadDebug("Cleaned filename", $filename);

// Define the root directory
$rootDir = dirname(__DIR__);
logDownloadDebug("Root directory", $rootDir);

// Create outputs directory if it doesn't exist
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'outputs';
if (!is_dir($outputDir)) {
    logDownloadDebug("Creating outputs directory", $outputDir);
    if (!mkdir($outputDir, 0755, true)) {
        logDownloadDebug("Failed to create outputs directory");
        http_response_code(500);
        echo 'Error: Failed to create outputs directory';
        exit;
    }
}

// Check if the file exists in the outputs directory
$filePath = $outputDir . DIRECTORY_SEPARATOR . $filename;
logDownloadDebug("Checking file path", $filePath);

if (!file_exists($filePath)) {
    logDownloadDebug("File not found", $filePath);
    
    // Check if we can find the job ID from the filename
    $jobId = intval(str_replace('.svg', '', $filename));
    logDownloadDebug("Extracted job ID", $jobId);
    
    if ($jobId > 0) {
        // Try to get the file from the database
        try {
            require_once __DIR__ . '/utils.php';
            
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT output_svg_path FROM image_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($job && !empty($job['output_svg_path'])) {
                $dbFilePath = $outputDir . DIRECTORY_SEPARATOR . $job['output_svg_path'];
                logDownloadDebug("Found file path in database", $dbFilePath);
                
                if (file_exists($dbFilePath)) {
                    $filePath = $dbFilePath;
                    logDownloadDebug("Using file path from database", $filePath);
                } else {
                    logDownloadDebug("File from database not found on disk", $dbFilePath);
                }
            } else {
                logDownloadDebug("Job not found in database or no output path");
            }
        } catch (Exception $e) {
            logDownloadDebug("Database error", $e->getMessage());
        }
    }
    
    // If we still don't have a valid file, create a placeholder SVG
    if (!file_exists($filePath)) {
        logDownloadDebug("Creating placeholder SVG");
        
        // Create a simple placeholder SVG
        $placeholderSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
<rect width="200" height="200" fill="#f0f0f0"/>
<text x="100" y="100" font-family="Arial" font-size="14" text-anchor="middle">
    SVG file not found
</text>
<text x="100" y="120" font-family="Arial" font-size="12" text-anchor="middle">
    Job ID: {$jobId}
</text>
</svg>
SVG;
        
        // Save the placeholder
        file_put_contents($filePath, $placeholderSvg);
        logDownloadDebug("Placeholder SVG created", $filePath);
    }
}

if (!file_exists($filePath)) {
    logDownloadDebug("File still not found after all attempts");
    http_response_code(404);
    echo 'Error: SVG file not found: ' . htmlspecialchars($filename);
    exit;
}

// Get file info
$fileSize = filesize($filePath);
$lastModified = filemtime($filePath);

logDownloadDebug("File info", [
    'path' => $filePath,
    'size' => $fileSize,
    'modified' => date('Y-m-d H:i:s', $lastModified)
]);

// Set appropriate headers for SVG download
header('Content-Type: image/svg+xml');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Prevent any output buffering
if (ob_get_level()) {
    ob_end_clean();
}

logDownloadDebug("Headers set, starting file output");

// Output the file
if (readfile($filePath) === false) {
    logDownloadDebug("Failed to read file");
    http_response_code(500);
    echo 'Error: Failed to read file';
    exit;
}

logDownloadDebug("File sent successfully");
logDownloadDebug("=== DOWNLOAD REQUEST END ===");
exit;
?>
