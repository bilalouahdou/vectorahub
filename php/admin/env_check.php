<?php
require_once __DIR__ . '/../config/bootstrap.php';

$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1']) ||
           preg_match('~^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)~', $_SERVER['REMOTE_ADDR'] ?? '');

if (!$isLocal) { 
    http_response_code(404); 
    exit; 
}

header('Content-Type: application/json');
echo json_encode([
  'APP_BASE_URL'=> (bool)getenv('APP_BASE_URL'),
  'APP_SECRET'=> substr((string)getenv('APP_SECRET'), 0, 6) !== '' ? '[set]' : '[missing]',
  'RUNNER_BASE_URL'=> (bool)getenv('RUNNER_BASE_URL'),
  'RUNNER_TOKEN'=> getenv('RUNNER_TOKEN') ? '[set]' : '[missing]',
], JSON_UNESCAPED_SLASHES);

