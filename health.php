<?php
// health.php
// Returns JSON health status for the application

header('Content-Type: application/json');
require_once __DIR__ . '/php/config.php';

// Initialize health report
$health = [
    'status'    => 'healthy',
    'timestamp' => date('c'),
    'checks'    => []
];

// Detect skip conditions: missing DATABASE_URL or explicit skip flag
$dbUrl = getenv('DATABASE_URL');
$skipDb = empty($dbUrl) || !empty($_GET['skip_db']);

// 1. Database check
if ($skipDb) {
    $health['checks']['database'] = [
        'success' => true,
        'skipped' => true,
        'note'    => $dbUrl ? 'Skip flag detected' : 'DATABASE_URL not set'
    ];
} else {
    try {
        // Check if pgsql driver is available
        $availableDrivers = PDO::getAvailableDrivers();
        if (!in_array('pgsql', $availableDrivers)) {
            throw new PDOException('PostgreSQL PDO driver not available. Available drivers: ' . implode(', ', $availableDrivers));
        }
        
        // Parse the DATABASE_URL
        $parts = parse_url($dbUrl);
        if (!$parts || !isset($parts['host'], $parts['user'], $parts['pass'])) {
            throw new PDOException('Invalid DATABASE_URL format. Missing required components.');
        }
        
        // Extract database name from path
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : 'postgres';
        if (empty($dbname)) {
            $dbname = 'postgres';
        }
        
        // Build proper DSN for PostgreSQL
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;sslmode=require", 
            $parts['host'],
            $parts['port'] ?? 5432,
            $dbname
        );
        
        // Create PDO connection with proper options
        $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 15,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Test connection with a simple query
        $stmt = $pdo->query('SELECT version() as version, current_database() as database');
        $result = $stmt->fetch();
        
        $health['checks']['database'] = [
            'success' => true,
            'database' => $result['database'] ?? 'unknown',
            'version' => substr($result['version'] ?? 'unknown', 0, 50) . '...'
        ];
        
    } catch (PDOException $e) {
        $health['checks']['database'] = [
            'success' => false,
            'error'   => 'Database connection failed: ' . $e->getMessage(),
            'code'    => $e->getCode()
        ];
        $health['status'] = 'unhealthy';
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'success' => false,
            'error'   => 'Unexpected error: ' . $e->getMessage()
        ];
        $health['status'] = 'unhealthy';
    }
}

// 2. PHP version check
$health['checks']['php'] = [
    'success' => true,
    'version' => PHP_VERSION,
    'sapi' => php_sapi_name()
];

// 3. Required extensions check
$requiredExtensions = [
    'pdo', 'pdo_pgsql', 'mbstring', 'json', 'gd', 'zip'
];
$extResults = [];
$allExtensionsLoaded = true;

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $extResults[$ext] = $loaded;
    if (!$loaded) {
        $allExtensionsLoaded = false;
    }
}

$health['checks']['extensions'] = [
    'success'    => $allExtensionsLoaded,
    'extensions' => $extResults
];

if (!$allExtensionsLoaded) {
    $health['status'] = 'unhealthy';
}

// 4. PDO drivers check (specific check for debugging)
$health['checks']['pdo_drivers'] = [
    'success' => true,
    'available' => PDO::getAvailableDrivers(),
    'pgsql_available' => in_array('pgsql', PDO::getAvailableDrivers())
];

// 5. Directory permissions check
$requiredDirs = ['uploads', 'outputs', 'temp'];
$permResults = [];
$allDirsWritable = true;

foreach ($requiredDirs as $dir) {
    $path = __DIR__ . "/$dir";
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $permResults[$dir] = [
        'exists' => $exists,
        'writable' => $writable,
        'path' => $path
    ];
    if (!$writable) {
        $allDirsWritable = false;
    }
}

$health['checks']['permissions'] = [
    'success'     => $allDirsWritable,
    'directories' => $permResults
];

if (!$allDirsWritable) {
    $health['status'] = 'unhealthy';
}

// 6. Environment variables check
$envVars = [
    'APP_ENV', 'APP_NAME', 'APP_URL', 'SUPABASE_URL', 
    'UPLOAD_MAX_SIZE', 'SESSION_LIFETIME', 'CSRF_TOKEN_EXPIRY'
];
$envResults = [];

foreach ($envVars as $var) {
    $value = getenv($var);
    $envResults[$var] = [
        'set' => $value !== false,
        'value' => $value !== false ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : null
    ];
}

$health['checks']['environment'] = [
    'success' => true,
    'variables' => $envResults,
    'database_url_set' => getenv('DATABASE_URL') !== false
];

// 7. Memory and disk space check
$health['checks']['system'] = [
    'success' => true,
    'memory_limit' => ini_get('memory_limit'),
    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
    'disk_free' => round(disk_free_space(__DIR__) / 1024 / 1024, 2) . ' MB'
];

// Return appropriate HTTP status code
http_response_code($health['status'] === 'healthy' ? 200 : 503);

// Output JSON with pretty printing
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>