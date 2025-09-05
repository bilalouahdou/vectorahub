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

// Start session for CSRF validation
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Enhanced logging function
function logDebug($message, $data = null) {
   $timestamp = date('Y-m-d H:i:s');
   $logMessage = "[upload_handler] $message";
   if ($data !== null) {
       $logMessage .= " | Data: " . print_r($data, true);
   }
   error_log($logMessage);
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
}

function parseSize($size) {
    $unit = strtolower(substr($size, -1));
    $value = (int)substr($size, 0, -1);
    switch ($unit) {
        case 'k': return $value * 1024;
        case 'm': return $value * 1024 * 1024;
        case 'g': return $value * 1024 * 1024 * 1024;
        default: return (int)$size;
    }
}

logDebug("=== UPLOAD HANDLER START ===");

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logDebug("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
        exit;
    }

    // CSRF check
    $sent = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $sess = $_SESSION['csrf_token'] ?? '';
    if ($sess === '' || $sent === '' || !hash_equals($sess, $sent)) {
        logDebug("CSRF token verification failed", ['sent' => substr($sent, 0, 8) . '...', 'session' => substr($sess, 0, 8) . '...']);
        jsonResponse(['success' => false, 'error' => 'CSRF token verification failed'], 400);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        jsonResponse(['success' => false, 'error' => 'No file field "image" found'], 400);
        exit;
    }

    $err = (int)$_FILES['image']['error'];
    if ($err !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL    => 'Partial upload; try again.',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
        ];
        $serverMax = (ini_get('upload_max_filesize') ?: 'unknown').' / '.(ini_get('post_max_size') ?: 'unknown');
        $errorMsg = ($map[$err] ?? ('File upload failed: '.$err))." (server limits: $serverMax)";
        logDebug("File upload error: " . $errorMsg);
        jsonResponse(['success' => false, 'error' => $errorMsg], 400);
        exit;
    }

    $file = $_FILES['image'];
    $originalFilename = $file['name'];
    $tempFilePath = $file['tmp_name'];
    $fileSize = (int)$file['size'];

    logDebug("Processing file", [
        'name' => $originalFilename,
        'size' => $fileSize,
        'tmp_path' => $tempFilePath
    ]);

    // Project cap: 5MB
    if ($fileSize > 5 * 1024 * 1024) {
        logDebug("File too large: " . $fileSize . " bytes");
        jsonResponse(['success' => false, 'error' => 'File too large (max 5MB)'], 400);
        exit;
    }

    // Runtime check for upload limits
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    if ($uploadMax && parseSize($uploadMax) < 5 * 1024 * 1024) {
        logDebug("Upload limit check: upload_max_filesize = $uploadMax, post_max_size = $postMax");
    }

    // Validate file type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tempFilePath);
    $allowed = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
    $ext = array_search($mimeType, $allowed, true);
    if ($ext === false) {
        logDebug("Invalid mime type: " . $mimeType);
        jsonResponse(['success' => false, 'error' => 'Only PNG/JPG files are allowed'], 400);
        exit;
    }

    // Ensure uploads directory exists and is writable
    $webRoot = dirname(__DIR__, 2); // Go up 2 levels: /var/www/html
    $uploadDir = $webRoot . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            logDebug("Failed to create upload directory: " . $uploadDir);
            jsonResponse(['success' => false, 'error' => 'Cannot create uploads directory'], 500);
            exit;
        }
    }

    if (!is_writable($uploadDir)) {
        logDebug("Upload directory not writable: " . $uploadDir);
        jsonResponse(['success' => false, 'error' => 'Uploads directory is not writable'], 500);
        exit;
    }

    // Generate unique filename: YYYYmmdd_HHMMSS_<8 hex>.<ext>
    $timestamp = date('Ymd_His');
    $randomHex = bin2hex(random_bytes(4)); // 8 hex characters
    $storedName = $timestamp . '_' . $randomHex . '.' . $ext;
    $inputPath = $uploadDir . '/' . $storedName;

    logDebug("Generated filename", [
        'original' => $originalFilename,
        'stored' => $storedName,
        'full_path' => $inputPath
    ]);

    // Move uploaded file
    if (!move_uploaded_file($tempFilePath, $inputPath)) {
        $uploadError = error_get_last();
        logDebug("File move failed", $uploadError);
        jsonResponse(['success' => false, 'error' => 'move_uploaded_file failed'], 500);
        exit;
    }

    // Verify file was created
    if (!file_exists($inputPath)) {
        logDebug("File not found after move: " . $inputPath);
        jsonResponse(['success' => false, 'error' => 'File was not saved correctly'], 500);
        exit;
    }

    // Public URL (force https; avoid redirects)
    $host = $_SERVER['HTTP_HOST'] ?? 'vectrahub.online';
    $publicUrl = 'https://' . $host . '/uploads/' . $storedName;
    $publicUrl = str_replace('\\','/',$publicUrl);

    logDebug("File upload complete", [
        'original_filename' => $originalFilename,
        'stored_filename' => $storedName,
        'public_url' => $publicUrl,
        'file_size' => filesize($inputPath)
    ]);

    // Return success with file information
    jsonResponse([
        'success' => true,
        'file_url' => $publicUrl,
        'stored_filename' => $storedName,
        'original_filename' => $originalFilename
    ]);

} catch (Exception $e) {
    logDebug("Unexpected error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Server error occurred'], 500);
}

logDebug("=== UPLOAD HANDLER END ===");
?>
