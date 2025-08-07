<?php
require_once '../config.php';
require_once '../utils.php';

// Set content type to JSON
header('Content-Type: application/json');

/**
 * Check if GPU runner is running, if not try to start it
 */
function ensureRunnerIsRunning($gpu_runner_url, $gpu_runner_token) {
    // First, check if runner is already running
    if (isRunnerHealthy($gpu_runner_url)) {
        return true;
    }
    
    // Runner is not running, try to start it
    error_log('GPU runner not running, attempting to start...');
    
    // Start the runner (this will trigger Windows Task or manual start)
    $start_success = startRunner();
    
    if (!$start_success) {
        error_log('Failed to start GPU runner');
        return false;
    }
    
    // Wait for runner to start up (max 30 seconds)
    $max_attempts = 30;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        sleep(1);
        if (isRunnerHealthy($gpu_runner_url)) {
            error_log('GPU runner started successfully');
            return true;
        }
        $attempt++;
    }
    
    error_log('GPU runner failed to start within 30 seconds');
    return false;
}

/**
 * Check if the GPU runner is healthy
 */
function isRunnerHealthy($gpu_runner_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($gpu_runner_url, '/') . '/health',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 200);
}

/**
 * Start the GPU runner
 */
function startRunner() {
    // Create a trigger file that will be monitored by Windows Task Scheduler
    $trigger_file = 'C:/vh_runner/start_trigger.txt';
    $trigger_dir = dirname($trigger_file);
    
    // Create directory if it doesn't exist
    if (!is_dir($trigger_dir)) {
        mkdir($trigger_dir, 0755, true);
    }
    
    // Write trigger file
    $success = file_put_contents($trigger_file, date('Y-m-d H:i:s') . " - Start request from web\n", FILE_APPEND);
    
    if ($success === false) {
        error_log('Failed to write trigger file: ' . $trigger_file);
        return false;
    }
    
    return true;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['input_url'])) {
        throw new Exception('input_url is required');
    }
    
    if (empty($input['mode'])) {
        throw new Exception('mode is required');
    }
    
    // Validate mode
    if (!in_array($input['mode'], ['bw', 'color'])) {
        throw new Exception('mode must be "bw" or "color"');
    }
    
    // Validate URL
    if (!filter_var($input['input_url'], FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid input_url');
    }
    
    // Get configuration
    $gpu_runner_url = getenv('GPU_RUNNER_URL');
    $gpu_runner_token = getenv('GPU_RUNNER_TOKEN');
    
    if (!$gpu_runner_url) {
        throw new Exception('GPU_RUNNER_URL not configured');
    }
    
    if (!$gpu_runner_token) {
        throw new Exception('GPU_RUNNER_TOKEN not configured');
    }
    
    // Check if GPU runner is running, if not try to start it
    $runner_ready = ensureRunnerIsRunning($gpu_runner_url, $gpu_runner_token);
    if (!$runner_ready) {
        throw new Exception('GPU runner is not available and could not be started');
    }
    
    // Prepare request to Flask API
    $runner_request = [
        'image_url' => $input['input_url']
    ];
    
    // Make request to Flask API  
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($gpu_runner_url, '/') . '/vectorize',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($runner_request),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120, // 2 minutes timeout
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // For development
        CURLOPT_SSL_VERIFYHOST => false  // For development
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception('Failed to connect to GPU runner: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        $error_message = $error_data['detail'] ?? 'GPU runner returned error: ' . $http_code;
        throw new Exception($error_message);
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        throw new Exception('Invalid response from GPU runner');
    }
    
    // Convert Flask API response to expected format
    if (isset($result['success']) && $result['success'] === true) {
        $svgFilename = $result['svg_filename'] ?? '';
        $downloadUrl = $result['download_url'] ?? '';
        
        // Construct full URL for the SVG file
        $svgUrl = rtrim($gpu_runner_url, '/') . $downloadUrl;
        
        $formattedResult = [
            'job_id' => uniqid(),
            'status' => 'done',
            'output' => [
                'local_path' => $svgUrl,
                'svg_filename' => $svgFilename
            ],
            'duration_ms' => 0
        ];
        
        // Log successful vectorization
        if (function_exists('logActivity')) {
            logActivity('gpu_vectorize', 'Vectorization completed', [
                'job_id' => $formattedResult['job_id'],
                'mode' => $input['mode'],
                'duration_ms' => $formattedResult['duration_ms']
            ]);
        }
        
        // Return the result
        echo json_encode([
            'success' => true,
            'data' => $formattedResult
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Flask API failed');
    }
    
} catch (Exception $e) {
    // Log error
    error_log('GPU Vectorize Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 