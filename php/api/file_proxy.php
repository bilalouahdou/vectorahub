<?php
declare(strict_types=1);
/** Standalone signed proxy for /uploads — NO includes, NO closing tag. */
header_remove('X-Powered-By');
header_remove('Content-Type'); // clear any default HTML

$name   = basename($_GET['name'] ?? '');
$sig    = (string)($_GET['sig'] ?? '');
$health = isset($_GET['health']) ? (int)$_GET['health'] : 0;

$secret = getenv('FILE_PROXY_SECRET') ?: '';
$root   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2), '/');
$path   = $root . '/uploads/' . $name;

// Health check endpoint
if ($health === 1) {
  header('Content-Type: application/json');
  
  $expected_hex = hash_hmac('sha256', $name, $secret);
  $expected_b64 = rtrim(strtr(base64_encode(hash_hmac('sha256', $name, $secret, true)), '+/', '-_'), '=');
  
  $sig_valid = false;
  $sig_kind = 'none';
  
  if ($secret !== '' && $sig !== '') {
    // Try hex signature first
    if (hash_equals($expected_hex, $sig)) {
      $sig_valid = true;
      $sig_kind = 'hex';
    }
    // Try base64 signature
    elseif (hash_equals($expected_b64, $sig)) {
      $sig_valid = true;
      $sig_kind = 'base64';
    }
  }
  
  echo json_encode([
    'name'      => $name,
    'has_sig'   => ($sig !== ''),
    'sig_valid' => $sig_valid,
    'path'      => $path,
    'exists'    => is_file($path),
    'size'      => is_file($path) ? filesize($path) : 0,
    'sig_kind'  => [
      'expected_hex'  => $expected_hex,
      'expected_b64'  => $expected_b64,
      'provided'      => $sig,
      'valid'         => $sig_valid,
      'type'          => $sig_kind
    ]
  ], JSON_UNESCAPED_SLASHES);
  exit;
}

// Validate required parameters
if ($name === '' || $sig === '' || $secret === '') { 
  http_response_code(400); 
  echo 'bad request'; 
  exit; 
}

// Verify signature (try both hex and base64)
$expected_hex = hash_hmac('sha256', $name, $secret);
$expected_b64 = rtrim(strtr(base64_encode(hash_hmac('sha256', $name, $secret, true)), '+/', '-_'), '=');

$sig_valid = false;
if (hash_equals($expected_hex, $sig) || hash_equals($expected_b64, $sig)) {
  $sig_valid = true;
}

if (!$sig_valid) { 
  http_response_code(403); 
  echo 'forbidden'; 
  exit; 
}

// Check file exists
if (!is_file($path)) { 
  http_response_code(404); 
  echo 'not found'; 
  exit; 
}

// Determine content type
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$ct  = match ($ext) {
  'jpg','jpeg' => 'image/jpeg',
  'png'        => 'image/png',
  'gif'        => 'image/gif',
  'webp'       => 'image/webp',
  'svg'        => 'image/svg+xml',
  default      => 'application/octet-stream'
};

// Ensure clean output - critical for binary data
while (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_clean(); }
if (function_exists('ob_start')) { @ob_start(); }

// Set headers after cleaning buffers
header('Content-Type: '.$ct);
header('Content-Length: '.filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Accept-Ranges: none');
header('X-Accel-Buffering: no');

// Force clean buffer and send file
if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_clean(); }
$fp = fopen($path, 'rb'); 
if ($fp !== false) {
    fpassthru($fp); 
    fclose($fp);
}
exit;