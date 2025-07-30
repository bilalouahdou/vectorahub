<?php
// db_test.php - Debug database connection issues
header('Content-Type: text/plain');

echo "=== PostgreSQL Driver Debug ===\n\n";

// 1. Check available PDO drivers
$drivers = PDO::getAvailableDrivers();
echo "Available PDO drivers: " . implode(', ', $drivers) . "\n\n";

// 2. Check if pgsql is in the list
$pgsqlAvailable = in_array('pgsql', $drivers);
echo "PostgreSQL driver available: " . ($pgsqlAvailable ? 'YES' : 'NO') . "\n\n";

// 3. Check loaded extensions
echo "Loaded extensions:\n";
$extensions = get_loaded_extensions();
foreach (['pdo', 'pdo_pgsql', 'pgsql'] as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? 'YES' : 'NO') . "\n";
}
echo "\n";

// 4. Check DATABASE_URL
$dbUrl = getenv('DATABASE_URL');
echo "DATABASE_URL set: " . ($dbUrl ? 'YES' : 'NO') . "\n";

if ($dbUrl) {
    echo "DATABASE_URL (masked): " . preg_replace('/:[^:@]*@/', ':***@', $dbUrl) . "\n\n";
    
    // 5. Parse URL
    $parts = parse_url($dbUrl);
    echo "Parsed components:\n";
    echo "- Host: " . ($parts['host'] ?? 'missing') . "\n";
    echo "- Port: " . ($parts['port'] ?? 'missing') . "\n";
    echo "- Database: " . (isset($parts['path']) ? ltrim($parts['path'], '/') : 'missing') . "\n";
    echo "- User: " . ($parts['user'] ?? 'missing') . "\n";
    echo "- Password: " . (isset($parts['pass']) ? '[SET]' : 'missing') . "\n\n";
    
    // 6. Test direct DSN construction
    if ($pgsqlAvailable && isset($parts['host'], $parts['user'], $parts['pass'])) {
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
            $parts['host'],
            $parts['port'] ?? 5432,
            ltrim($parts['path'] ?? '/postgres', '/')
        );
        
        echo "Testing connection with DSN...\n";
        echo "DSN (masked): " . str_replace($parts['pass'], '***', $dsn) . "\n";
        
        try {
            $pdo = new PDO($dsn, $parts['user'], $parts['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 15
            ]);
            
            $version = $pdo->query('SELECT version()')->fetchColumn();
            echo "✅ SUCCESS: Connected to PostgreSQL\n";
            echo "PostgreSQL version: " . substr($version, 0, 50) . "...\n";
            
        } catch (PDOException $e) {
            echo "❌ FAILED: " . $e->getMessage() . "\n";
            echo "Error code: " . $e->getCode() . "\n";
        }
    }
} else {
    echo "\nNo DATABASE_URL to test.\n";
}

echo "\n=== End Debug ===\n";
?>