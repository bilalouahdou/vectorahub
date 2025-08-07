<?php
// Debug URL script
echo "Debug URL Information\n";
echo "=====================\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'not set') . "\n";

// Check if APP_URL is correctly set
require_once 'php/config.php';
echo "APP_URL (from config): " . (defined('APP_URL') ? APP_URL : 'not set') . "\n";

// Show current working directory
echo "Current Working Directory: " . getcwd() . "\n";

echo "\nFull \$_SERVER array:\n";
print_r($_SERVER);
?>

