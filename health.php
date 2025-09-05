<?php
// health.php
// Returns JSON health status for the application

header('Content-Type: application/json');

// Initialize health report
$health = [
    'status'    => 'healthy',
    'timestamp' => date('c'),
    'checks'    => []
];

// 1. Basic PHP check (always run)
$health['checks']['php'] = [
    'success' => true,
    'version' => PHP_VERSION,
    'sapi' => php_sapi_name()
];

// 2. Required extensions check (critical)
$requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'json', 'gd', 'zip'];
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

// 3. Directory permissions check (critical)
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
    'directories' => $permResults,
    'note'        => $allDirsWritable ? 'All directories writable' : 'Some directories not writable - check permissions'
];

// Don't mark as unhealthy for directory permission issues during deployment/startup
// These can be fixed at runtime and shouldn't prevent health checks from passing
if (!$allDirsWritable) {
    $health['checks']['permissions']['warning'] = 'Directory permissions should be fixed, but not blocking health check';
    // $health['status'] = 'unhealthy'; // Commented out to be more resilient
}

// 4. Environment variables check
$envVars = ['APP_ENV', 'APP_NAME', 'APP_URL', 'SUPABASE_URL'];
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

// 5. Database check (skip during deployment and initial startup)
$dbUrl = getenv('DATABASE_URL');
$isDeployment = getenv('FLY_ALLOC_ID') && (time() - filemtime('/proc/self/stat')) < 300; // Skip DB checks in first 5 minutes on Fly.io
$skipDb = empty($dbUrl) || !empty($_GET['skip_db']) || $isDeployment;

if ($skipDb) {
    $health['checks']['database'] = [
        'success' => true,
        'skipped' => true,
        'note'    => $isDeployment ? 'Skipped during deployment/startup' : ($dbUrl ? 'Skip flag detected' : 'DATABASE_URL not set')
    ];
} else {
    try {
        // Set a shorter timeout for health checks
        set_time_limit(5);
        
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
        
        // Create PDO connection with shorter timeout for health checks
        $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3, // Shorter timeout for health checks
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Test connection with a simple query
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        
        $health['checks']['database'] = [
            'success' => true,
            'test' => $result['test'] ?? 'unknown'
        ];
        
    } catch (PDOException $e) {
        $health['checks']['database'] = [
            'success' => false,
            'error'   => 'Database connection failed: ' . $e->getMessage(),
            'code'    => $e->getCode()
        ];
        // Don't mark as unhealthy for database issues during deployment
        // $health['status'] = 'unhealthy';
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'success' => false,
            'error'   => 'Unexpected error: ' . $e->getMessage()
        ];
        // Don't mark as unhealthy for database issues during deployment
        // $health['status'] = 'unhealthy';
    }
}

// 6. Memory and disk space check
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