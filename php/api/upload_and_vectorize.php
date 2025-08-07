<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../utils.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Debug: Log that we received a request
error_log('Upload and vectorize: Received POST request');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Authentication required');
    }
    
    // Get user ID from session
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('User ID not found in session');
    }
    
    // Debug logging
    error_log('Upload and vectorize: Starting processing for user ID: ' . $userId);
    
    // Check if we have a file upload
    if (!isset($_FILES['vectorize_file']) || $_FILES['vectorize_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $uploadedFile = $_FILES['vectorize_file'];
    $originalFilename = $uploadedFile['name'];
    $tmpPath = $uploadedFile['tmp_name'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Check if mime_content_type function exists
    if (function_exists('mime_content_type')) {
        $fileType = mime_content_type($tmpPath);
    } else {
        // Fallback: check file extension
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $extensionMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $fileType = $extensionMap[$extension] ?? 'unknown';
    }
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.');
    }
    
    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 10MB.');
    }
    
    // Create unique filename
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $uniqueFilename = uniqid('upload_') . '.' . $extension;
    $uploadDir = __DIR__ . '/../../uploads/';
    $uploadPath = $uploadDir . $uniqueFilename;
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    error_log('Upload and vectorize: Attempting to move file from ' . $tmpPath . ' to ' . $uploadPath);
    
    // Move uploaded file
    if (!move_uploaded_file($tmpPath, $uploadPath)) {
        throw new Exception('Failed to save uploaded file to: ' . $uploadPath);
    }
    
    error_log('Upload and vectorize: File uploaded successfully to ' . $uploadPath);
    
    // Create public URL for the uploaded file
    $imageUrl = APP_URL . '/uploads/' . $uniqueFilename;
    
    // Get the requested mode
    $mode = $_POST['requested_mode'] ?? 'color';
    if (!in_array($mode, ['bw', 'color'])) {
        $mode = 'color';
    }
    
    // Call the Flask API via Cloudflare tunnel
    $runnerUrl = getenv('GPU_RUNNER_URL') ?: 'http://127.0.0.1:5000';
    $apiUrl = rtrim($runnerUrl, '/') . '/vectorize';
    error_log('Upload and vectorize: Calling Flask API: ' . $apiUrl);
    
    // Create form data for file upload
    $postData = [
        'image' => new CURLFile($uploadPath, $fileType, $originalFilename)
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Clean up uploaded file after processing
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    if ($curlError) {
        throw new Exception('Failed to process with GPU: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error'] ?? 'GPU processing failed with status: ' . $httpCode;
        throw new Exception($errorMessage);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception('Invalid response from GPU runner');
    }
    
    // Flask API returns: {success, svg_filename, download_url}
    if (isset($result['success']) && $result['success'] === true) {
        // Convert Flask API response to expected format
        $svgFilename = $result['svg_filename'] ?? '';
        $downloadUrl = $result['download_url'] ?? '';
        
        // Construct full URL for the SVG file
        $svgUrl = rtrim($runnerUrl, '/') . $downloadUrl;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'job_id' => uniqid(),
                'status' => 'done',
                'output' => [
                    'local_path' => $svgUrl,
                    'svg_filename' => $svgFilename
                ],
                'duration_ms' => 0,
                'original_filename' => $originalFilename,
                'upload_method' => 'file_upload'
            ]
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Flask API failed');
    }
    
} catch (Exception $e) {
    // Clean up uploaded file on error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log('Upload and vectorize error: ' . $e->getMessage());
    error_log('Upload and vectorize error trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>