<?php
/**
 * Simple test endpoint to verify webhook routing
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Webhook endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI']
]);
?>
