<?php
/**
 * Direct SVG Download Script
 * This script bypasses Apache configuration and directly serves SVG files
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the filename from the URL
$filename = isset($_GET['file']) ? $_GET['file'] : '';

// Security check - only allow SVG files
if (empty($filename) || pathinfo($filename, PATHINFO_EXTENSION) !== 'svg') {
    header('HTTP/1.0 400 Bad Request');
    echo 'Error: Invalid file request. Only SVG files are allowed.';
    exit;
}

// Set the path to the outputs directory
$outputDir = __DIR__ . '/outputs/';
$filePath = $outputDir . $filename;

// Check if the file exists
if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Error: File not found: ' . htmlspecialchars($filename);
    exit;
}

// Log the download attempt
error_log("Attempting to serve SVG file: $filePath");

// Get the file size
$fileSize = filesize($filePath);

// Set the appropriate headers for SVG download
header('Content-Type: image/svg+xml');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Read the file and output it
readfile($filePath);
exit;
