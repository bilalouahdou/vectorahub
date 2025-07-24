<?php
// Catch any fatal errors and return JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['error' => 'Server error: ' . $error['message']]);
    }
});

// Set error handler to convert all errors to exceptions
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Turn off HTML error display and use JSON responses only
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set JSON content type immediately
header('Content-Type: application/json');

// Enhanced logging function
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

logDebug("=== UPLOAD HANDLER START ===");
logDebug("Current working directory", getcwd());
logDebug("Script filename", __FILE__);
logDebug("Script directory", __DIR__);

try {
    // Since upload_handler.php is in the php folder, config.php is in the same directory
    $phpDir = __DIR__; // This is the php directory
    $rootDir = dirname($phpDir); // This is the parent directory (test)
    
    logDebug("PHP directory", $phpDir);
    logDebug("Root directory", $rootDir);
    
    // Files are in the same directory as this script
    $configPath = $phpDir . DIRECTORY_SEPARATOR . 'config.php';
    $utilsPath = $phpDir . DIRECTORY_SEPARATOR . 'utils.php';
    $apiClientPath = $phpDir . DIRECTORY_SEPARATOR . 'python_api_client.php';
    
    logDebug("Config path", $configPath);
    logDebug("Utils path", $utilsPath);
    logDebug("API client path", $apiClientPath);
    
    // Check if files exist
    if (!file_exists($configPath)) {
        throw new Exception("config.php not found at: $configPath");
    }
    
    if (!file_exists($utilsPath)) {
        throw new Exception("utils.php not found at: $utilsPath");
    }
    
    if (!file_exists($apiClientPath)) {
        throw new Exception("python_api_client.php not found at: $apiClientPath");
    }
    
    // Include files in the correct order
    require_once $configPath;
    require_once $utilsPath;
    require_once $apiClientPath;
    
    logDebug("All required files loaded successfully");
    
} catch (Exception $e) {
    logDebug("File loading error", $e->getMessage());
    echo json_encode(['error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

try {
    logDebug("Checking authentication");
    redirectIfNotAuth();
    logDebug("Authentication passed");
} catch (Exception $e) {
    logDebug("Authentication error", $e->getMessage());
    echo json_encode(['error' => 'Authentication required: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Invalid request method");
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

try {
    logDebug("Verifying CSRF token");
    $csrfToken = $_POST['csrf_token'] ?? '';
    logDebug("CSRF token received", $csrfToken);
    
    if (!verifyCsrfToken($csrfToken)) {
        throw new Exception('CSRF token verification failed');
    }
    logDebug("CSRF token verified");
} catch (Exception $e) {
    logDebug("CSRF error", $e->getMessage());
    echo json_encode(['error' => 'Invalid CSRF token: ' . $e->getMessage()]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    logDebug("No user ID in session");
    echo json_encode(['error' => 'User session invalid']);
    exit;
}
logDebug("Processing for user", $userId);

// Check if this is a bulk upload
$uploadMode = $_POST['upload_mode'] ?? 'single';
$isBulkUpload = ($uploadMode === 'bulk');

logDebug("Upload mode", $uploadMode);

// Check coins with enhanced error handling
try {
    logDebug("Checking user coins");
    $coinsRemaining = getUserCoinsRemaining($userId);
    logDebug("Coins remaining", $coinsRemaining);
    
    if ($coinsRemaining < 1) {
        echo json_encode(['error' => 'Insufficient coins. Please upgrade your plan.']);
        exit;
    }
} catch (Exception $e) {
    logDebug("Coins check error", $e->getMessage());
    echo json_encode(['error' => 'Error checking account balance: ' . $e->getMessage()]);
    exit;
}

try {
    logDebug("Connecting to database");
    $pdo = connectDB();
    logDebug("Database connected successfully");
    
    $inputPath = '';
    $isUrl = false;
    $originalFileName = '';
    
    // Handle file upload or URL
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        logDebug("Processing file upload", [
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ]);
        
        // Extract original filename without extension
        $originalFileName = pathinfo($file['name'], PATHINFO_FILENAME);
        logDebug("Original filename (without extension)", $originalFileName);
        
        // Validate file
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        
        // Enhanced MIME type detection
        $mimeType = 'unknown';
        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            finfo_close($fileInfo);
            logDebug("MIME type detected via finfo", $mimeType);
        } else {
            // Fallback to file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mimeType = match($extension) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'unknown'
            };
            logDebug("MIME type detected via extension", $mimeType);
        }
        
        if (!in_array($mimeType, $allowedTypes)) {
            logDebug("Invalid file type", $mimeType);
            echo json_encode(['error' => 'Only PNG and JPEG files are allowed. Detected: ' . $mimeType]);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            logDebug("File too large", $file['size']);
            echo json_encode(['error' => 'File size must be less than 5MB. Size: ' . round($file['size']/1024/1024, 2) . 'MB']);
            exit;
        }
        
        $extension = $mimeType === 'image/png' ? '.png' : '.jpg';
        $fileName = 'upload_' . uniqid() . $extension;
        
        // Use uploads directory in the root (parent of php directory)
        $uploadDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        logDebug("Upload directory", $uploadDir);
        
        if (!is_dir($uploadDir)) {
            logDebug("Creating upload directory");
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory: $uploadDir");
            }
        }
        
        if (!is_writable($uploadDir)) {
            throw new Exception("Upload directory is not writable: $uploadDir");
        }
        
        $inputPath = $uploadDir . $fileName;
        logDebug("Moving file to", $inputPath);
        
        if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
            $uploadError = error_get_last();
            logDebug("File move failed", $uploadError);
            throw new Exception("Failed to move uploaded file. Error: " . ($uploadError['message'] ?? 'Unknown'));
        }
        
        if (!file_exists($inputPath)) {
            throw new Exception("File was not created at expected location: $inputPath");
        }
        
        logDebug("File uploaded successfully", $inputPath);
        
        // Add image optimization for large images
        require_once $phpDir . DIRECTORY_SEPARATOR . 'image_optimizer.php';

        try {
            $imageInfo = getimagesize($inputPath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $fileSize = filesize($inputPath);
                
                logDebug("Original image info", ['width' => $width, 'height' => $height, 'size' => $fileSize]);
                
                // Check if image needs optimization
                $settings = ImageOptimizer::getRecommendedSettings($width, $height, $fileSize);
                logDebug("Optimization settings", $settings);
                
                if ($width > 2048 || $height > 2048 || $fileSize > 3 * 1024 * 1024) {
                    logDebug("Large image detected, optimizing...");
                    
                    $optimizer = new ImageOptimizer($settings['max_dimension'], 5 * 1024 * 1024, $settings['quality']);
                    $optimizedPath = $optimizer->optimizeImage($inputPath);
                    
                    if ($optimizedPath !== $inputPath) {
                        // Replace original with optimized version
                        unlink($inputPath);
                        rename($optimizedPath, $inputPath);
                        logDebug("Image optimized successfully");
                    }
                }
            }
        } catch (Exception $e) {
            logDebug("Image optimization failed, continuing with original", $e->getMessage());
            // Continue with original image if optimization fails
        }
        
    } elseif (!empty($_POST['image_url'])) {
        $url = filter_var($_POST['image_url'], FILTER_VALIDATE_URL);
        if (!$url) {
            logDebug("Invalid URL", $_POST['image_url']);
            echo json_encode(['error' => 'Invalid URL format']);
            exit;
        }
        
        $parsedUrl = parse_url($url);
        if (!in_array($parsedUrl['scheme'] ?? '', ['http', 'https'])) {
            logDebug("Invalid URL scheme", $parsedUrl['scheme'] ?? 'none');
            echo json_encode(['error' => 'Only HTTP and HTTPS URLs are allowed']);
            exit;
        }
        
        // Extract filename from URL
        $urlPath = parse_url($url, PHP_URL_PATH);
        $urlFilename = basename($urlPath);
        $originalFileName = pathinfo($urlFilename, PATHINFO_FILENAME);
        
        // If no filename in URL, use a default
        if (empty($originalFileName)) {
            $originalFileName = 'image_from_url';
        }
        
        logDebug("Original filename from URL", $originalFileName);
        
        $isUrl = true;
        $fileName = 'url_' . uniqid();
        logDebug("Processing URL", $url);
    } else {
        logDebug("No image provided");
        echo json_encode(['error' => 'No image file or URL provided']);
        exit;
    }
    
    // Clean the original filename for safe use
    $originalFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFileName);
    if (empty($originalFileName)) {
        $originalFileName = 'vectorized_image';
    }
    logDebug("Cleaned original filename", $originalFileName);
    
    // Get bulk upload info if applicable
    $bulkGroupId = $_POST['bulk_group_id'] ?? null;
    $bulkPosition = $_POST['bulk_position'] ?? 0;
    
    // Create job record
    logDebug("Creating job record");
    $stmt = $pdo->prepare("INSERT INTO image_jobs (user_id, original_image_path, original_filename, status, bulk_group_id, bulk_position) VALUES (?, ?, ?, 'queued', ?, ?)");
    $stmt->execute([$userId, $fileName, $originalFileName, $bulkGroupId, $bulkPosition]);
    $jobId = $pdo->lastInsertId();
    logDebug("Created job", $jobId);
    
    // Deduct coin
    logDebug("Deducting coin");
    $stmt = $pdo->prepare("INSERT INTO coin_usage (user_id, image_job_id, coins_used) VALUES (?, ?, 1)");
    $stmt->execute([$userId, $jobId]);
    
    // Update job status
    logDebug("Updating job status to processing");
    $stmt = $pdo->prepare("UPDATE image_jobs SET status = 'processing' WHERE id = ?");
    $stmt->execute([$jobId]);
    
    // Process with API
    try {
        logDebug("Initializing Python API client");
        $apiClient = new PythonApiClient('http://localhost:5000');
        
        // Quick health check
        logDebug("Checking API health");
        $healthCheck = $apiClient->healthCheck();
        logDebug("Health check result", $healthCheck);
        
        if ($healthCheck['http_code'] !== 200) {
            throw new Exception("Python API not available. Status: " . $healthCheck['http_code']);
        }
        
        logDebug("API available, starting processing");
        
        if ($isUrl) {
            logDebug("Processing URL with API");
            $result = $apiClient->vectorizeUrl($_POST['image_url']);
        } else {
            logDebug("Processing file with API");
            $result = $apiClient->vectorizeFile($inputPath);
        }
        
        logDebug("API processing result", $result);
        
        if ($result['success']) {
            // Use original filename for SVG output
            $svgFileName = $originalFileName . '.svg';
            $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR;
            
            logDebug("Output directory", $outputDir);
            logDebug("SVG filename will be", $svgFileName);
            
            if (!is_dir($outputDir)) {
                logDebug("Creating output directory");
                if (!mkdir($outputDir, 0755, true)) {
                    throw new Exception("Failed to create output directory: $outputDir");
                }
            }
            
            if (!is_writable($outputDir)) {
                throw new Exception("Output directory is not writable: $outputDir");
            }
            
            $svgPath = $outputDir . $svgFileName;
            
            // If file already exists, add a number suffix
            $counter = 1;
            $baseSvgPath = $svgPath;
            while (file_exists($svgPath)) {
                $svgFileName = $originalFileName . '_' . $counter . '.svg';
                $svgPath = $outputDir . $svgFileName;
                $counter++;
            }
            
            logDebug("Final SVG path", $svgPath);
            logDebug("Final SVG filename", $svgFileName);
            
            // Download SVG from API
            $downloadSuccess = $apiClient->downloadSvg($result['svg_filename'], $svgPath);
            
            if (!$downloadSuccess || !file_exists($svgPath)) {
                throw new Exception("SVG file was not downloaded successfully to: $svgPath");
            }
            
            $svgSize = filesize($svgPath);
            logDebug("SVG downloaded successfully", ['path' => $svgPath, 'size' => $svgSize]);
            
            // Update job
            logDebug("Updating job status to done");
            $stmt = $pdo->prepare("UPDATE image_jobs SET status = 'done', output_svg_path = ? WHERE id = ?");
            $stmt->execute([$svgFileName, $jobId]);
            
            // Cleanup
            if (!$isUrl && file_exists($inputPath)) {
                unlink($inputPath);
                logDebug("Cleaned up input file", $inputPath);
            }
            
            logDebug("Job completed successfully");

            // Return response with proper download URL
            echo json_encode([
                'success' => true,
                'job_id' => $jobId,
                'status' => 'done',
                'svg_url' => 'download.php?file=' . urlencode($svgFileName),
                'original_filename' => $originalFileName,
                'svg_filename' => $svgFileName,
                'bulk_group_id' => $bulkGroupId,
                'bulk_position' => $bulkPosition
            ]);
            
        } else {
            throw new Exception($result['error'] ?? 'API processing failed with unknown error');
        }
        
    } catch (Exception $e) {
        logDebug("API processing error", $e->getMessage());
        
        // Update job as failed
        $stmt = $pdo->prepare("UPDATE image_jobs SET status = 'failed' WHERE id = ?");
        $stmt->execute([$jobId]);
        
        // Cleanup
        if (!$isUrl && file_exists($inputPath)) {
            unlink($inputPath);
            logDebug("Cleaned up input file after error", $inputPath);
        }
        
        echo json_encode(['error' => 'Processing failed: ' . $e->getMessage()]);
    }
    
} catch (Exception $e) {
    logDebug("Handler error", $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

logDebug("=== UPLOAD HANDLER END ===");
?>
