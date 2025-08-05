<?php
// startup.php - Simple startup test script
// This can be used to verify basic functionality during deployment

header('Content-Type: text/plain');

echo "=== VectraHub Startup Test ===\n";
echo "Timestamp: " . date('c') . "\n\n";

// 1. PHP Version
echo "1. PHP Version: " . PHP_VERSION . "\n";

// 2. Required Extensions
$requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'json', 'gd', 'zip'];
echo "2. Required Extensions:\n";
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   - $ext: " . ($loaded ? "✓" : "✗") . "\n";
}

// 3. Directory Permissions
$requiredDirs = ['uploads', 'outputs', 'temp'];
echo "3. Directory Permissions:\n";
foreach ($requiredDirs as $dir) {
    $path = __DIR__ . "/$dir";
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    echo "   - $dir: " . ($exists ? "exists" : "missing") . ", " . ($writable ? "writable" : "not writable") . "\n";
}

// 4. Environment Variables
echo "4. Environment Variables:\n";
$envVars = ['APP_ENV', 'APP_NAME', 'APP_URL', 'SUPABASE_URL', 'DATABASE_URL'];
foreach ($envVars as $var) {
    $value = getenv($var);
    echo "   - $var: " . ($value !== false ? "set" : "not set") . "\n";
}

// 5. Database Connection (if DATABASE_URL is set)
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    echo "5. Database Connection:\n";
    try {
        $parts = parse_url($dbUrl);
        if ($parts && isset($parts['host'], $parts['user'], $parts['pass'])) {
            $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : 'postgres';
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;sslmode=require", 
                $parts['host'],
                $parts['port'] ?? 5432,
                $dbname
            );
            
            $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            $stmt = $pdo->query('SELECT 1 as test');
            $result = $stmt->fetch();
            echo "   - Database: ✓ Connected successfully\n";
        } else {
            echo "   - Database: ✗ Invalid DATABASE_URL format\n";
        }
    } catch (Exception $e) {
        echo "   - Database: ✗ Connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "5. Database Connection: Skipped (DATABASE_URL not set)\n";
}

echo "\n=== Startup Test Complete ===\n";
?> 