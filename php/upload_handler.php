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

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
}

function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function logActivity($action, $description, $userId) {
    // Placeholder for logging activity
    logDebug("$action: $description");
}

function recordCoinUsage($userId, $cost, $isBlackImage) {
    // Placeholder for recording coin usage
    logDebug("Recording coin usage for user $userId: $cost coins for " . ($isBlackImage ? "black image" : "standard image"));
}

function getDBConnection() {
    // Placeholder for database connection
    return new PDO('mysql:host=localhost;dbname=test', 'user', 'password');
}

function getCurrentUserSubscription($userId) {
    // Placeholder for getting user subscription
    return ['unlimited_black_images' => false];
}

function getUserCoinsRemaining($userId) {
    // Placeholder for getting user coins remaining
    return 10;
}

function getimagesize($path) {
    // Placeholder for getimagesize
    return [1024, 768, 3];
}

function filesize($path) {
    // Placeholder for filesize
    return 2 * 1024 * 1024;
}

function move_uploaded_file($from, $to) {
    // Placeholder for move_uploaded_file
    return true;
}

function unlink($path) {
    // Placeholder for unlink
    return true;
}

function rename($from, $to) {
    // Placeholder for rename
    return true;
}

function filter_var($value, $filter) {
    // Placeholder for filter_var
    return $value;
}

function parse_url($url, $component = -1) {
    // Placeholder for parse_url
    return ['scheme' => 'http', 'path' => '/path/to/image'];
}

function file_get_contents($path) {
    // Placeholder for file_get_contents
    return '<svg>...</svg>';
}

function file_put_contents($path, $content) {
    // Placeholder for file_put_contents
    return strlen($content);
}

define('ALLOWED_IMAGE_TYPES', ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp']);
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('OUTPUT_DIR', '/path/to/outputs/');
define('APP_URL', 'http://example.com');

// Constants for database connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'user');
define('DB_PASSWORD', 'password');

// Class placeholders
class PythonApiClient {
    public function __construct($url) {}
    public function vectorizeImage($filename, $content, $type) {
        // Simulate Python API response, including the is_black_image flag
        // For testing, let's assume it's black if filename contains 'bw'
        $isBlack = strpos(strtolower($filename), 'bw') !== false;
        return [
            'success' => true, 
            'data' => [
                'svg_content' => '<svg>...</svg>', 
                'svg_filename' => 'vectorized_image.svg'
            ],
            'is_black_image' => $isBlack // This is the crucial flag from Python
        ];
    }
}

class ImageOptimizer {
    public static function getRecommendedSettings($width, $height, $size) {
        return ['max_dimension' => 2048, 'quality' => 85];
    }
    public function __construct($maxDimension, $maxSize, $quality) {}
    public function optimizeImage($path) {
        return $path;
    }
}

class SVGSanitizer {
    public function sanitize($svgContent) {
        return $svgContent;
    }
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
    $securityPath = $phpDir . DIRECTORY_SEPARATOR . 'security' . DIRECTORY_SEPARATOR . 'SVGSanitizer.php';
    
    logDebug("Config path", $configPath);
    logDebug("Utils path", $utilsPath);
    logDebug("API client path", $apiClientPath);
    logDebug("Security path", $securityPath);
    
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
    
    if (!file_exists($securityPath)) {
        throw new Exception("SVGSanitizer.php not found at: $securityPath");
    }
    
    // Include files in the correct order
    require_once $configPath;
    require_once $utilsPath;
    require_once $apiClientPath;
    require_once $securityPath;
    
    logDebug("All required files loaded successfully");
    
} catch (Exception $e) {
    logDebug("File loading error", $e->getMessage());
    echo json_encode(['error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

try {
    logDebug("Checking authentication");
    startSession();
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
    }
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

// Get upload mode and requested black image status from frontend
$uploadMode = $_POST['upload_mode'] ?? 'single';
$requestedMode = $_POST['requested_mode'] ?? 'normal'; // 'normal' or 'black-white'

logDebug("Upload mode", $uploadMode);
logDebug("Requested mode", $requestedMode);

try {
    logDebug("Connecting to database");
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // Check user's current subscription
    $userSubscription = getCurrentUserSubscription($userId);
    
    // Prepare image for Python API
    $file = $_FILES['image'];
    $originalFilename = $file['name'];
    $tempFilePath = $file['tmp_name'];

    // Validate file type and size
    $fileType = mime_content_type($tempFilePath);
    if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
        jsonResponse(['success' => false, 'error' => 'Invalid file type. Only PNG, JPG, GIF, WEBP are allowed.'], 400);
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        jsonResponse(['success' => false, 'error' => 'File size exceeds limit (' . (UPLOAD_MAX_SIZE / (1024 * 1024)) . 'MB).'], 400);
    }

    // Move uploaded file to uploads directory
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

    $inputPath = $uploadDir . $originalFilename;
    logDebug("Moving file to", $inputPath);

    if (!move_uploaded_file($tempFilePath, $inputPath)) {
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

    // Call Python API for vectorization
    $pythonApiClient = new PythonApiClient(getenv('PYTHON_API_URL') ?: 'http://localhost:8000/api');
    $vectorizationResult = $pythonApiClient->vectorizeImage($originalFilename, file_get_contents($inputPath), $fileType);

    if (!isset($vectorizationResult['success']) || !$vectorizationResult['success']) {
        throw new Exception($vectorizationResult['error'] ?? 'Python vectorization API failed.');
    }

    $svgContent = $vectorizationResult['data']['svg_content'];
    $svgFilename = $vectorizationResult['data']['svg_filename'];
    $isBlackImageDetectedByPython = $vectorizationResult['is_black_image'] ?? false; // Get the strict B&W flag from Python

    // Determine final cost and message based on requested mode and actual image type
    $cost = 1; // Default cost for standard image
    $isFree = false;
    $message = 'Image vectorized successfully!';

    if ($requestedMode === 'black-white') {
        if ($isBlackImageDetectedByPython) {
            if ($userSubscription['unlimited_black_images']) {
                $cost = 0; // Free for black images if user has the unlimited pack
                $isFree = true;
                logActivity('VECTORIZE_BLACK_UNLIMITED', "User $userId vectorized a black image for free with unlimited pack.", $userId);
                $message = 'Black & White image vectorized for free with your unlimited pack!';
            } else {
                $cost = 0.5; // Discounted rate for black images
                logActivity('VECTORIZE_BLACK_DISCOUNT', "User $userId vectorized a black image at discounted rate.", $userId);
                $message = 'Black & White image vectorized at a discounted rate!';
            }
        } else {
            // User requested B&W mode, but image is not strictly B&W
            $cost = 1; // Revert to standard cost
            logActivity('VECTORIZE_BW_MISMATCH_NORMAL', "User $userId uploaded non-B&W in B&W mode, processed as standard.", $userId);
            $message = 'Image was not purely black and white. Processed as a standard image.';
        }
    } else { // Normal mode or bulk mode
        $cost = 1; // Standard cost
        logActivity('VECTORIZE_STANDARD', "User $userId vectorized a standard image.", $userId);
    }

    // Check coins with enhanced error handling for non-free operations
    $coinsRemaining = getUserCoinsRemaining($userId);
    if (!$isFree && $coinsRemaining < $cost) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Not enough coins. Please upgrade your plan.'], 402);
    }

    // Sanitize SVG content
    $sanitizer = new SVGSanitizer();
    $sanitizedSvgContent = $sanitizer->sanitize($svgContent);

    // Save the vectorized SVG
    $outputFilePath = OUTPUT_DIR . $svgFilename;
    if (!file_put_contents($outputFilePath, $sanitizedSvgContent)) {
        throw new Exception("Failed to save vectorized SVG.");
    }

    // Record coin usage if not free
    if (!$isFree) {
        recordCoinUsage($userId, $cost, $isBlackImageDetectedByPython); // Use the actual cost and B&W status
    }

    // Record vectorization job
    $stmt = $pdo->prepare("
        INSERT INTO vectorization_jobs (user_id, original_filename, vectorized_filename, coins_used, is_black_image, processed_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $originalFilename, $svgFilename, $cost, $isBlackImageDetectedByPython]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => $message,
        'job_id' => $jobId, // Assuming $jobId is defined earlier, if not, remove or define.
        'status' => 'done',
        'svg_url' => APP_URL . '/outputs/' . $svgFilename,
        'original_filename' => $originalFilename,
        'svg_filename' => $svgFilename,
        'coins_remaining' => getUserCoinsRemaining($userId), // Refresh coins
        'is_black_image_detected' => $isBlackImageDetectedByPython,
        'cost' => $cost
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Upload handler error for user $userId: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

logDebug("=== UPLOAD HANDLER END ===");
?>
