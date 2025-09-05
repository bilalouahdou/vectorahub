<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../services/Http.php';
require_once __DIR__ . '/../services/RunnerClient.php';

// Setup error logging
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
if (!is_dir(__DIR__ . '/../logs')) { @mkdir(__DIR__ . '/../logs', 0775, true); }

header_remove('X-Powered-By');
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

// Debug trap → JSON errors
$raw = file_get_contents('php://input') ?: '';
$in  = json_decode($raw, true);
$dbg = 0;
if (is_array($in) && !empty($in['debug'])) $dbg = (int)$in['debug'];
if (!$dbg && isset($_GET['debug'])) $dbg = (int)$_GET['debug'];
if ($dbg) {
  ini_set('display_errors','0'); ini_set('log_errors','1');
  set_exception_handler(function(Throwable $e){
    http_response_code(200);
    echo json_encode(['success'=>false,'code'=>'server_exception','error'=>'Server error','message'=>$e->getMessage()], JSON_UNESCAPED_SLASHES); exit;
  });
  set_error_handler(function($no,$str,$file,$line){
    http_response_code(200);
    echo json_encode(['success'=>false,'code'=>'php_error','error'=>'Server error','message'=>$str,'where'=>basename($file).':'.$line], JSON_UNESCAPED_SLASHES); return true;
  });
}

// Validate and parse input
if (!is_array($in)) { 
    http_response_code(400); 
    echo json_encode(['success'=>false,'error'=>'Invalid JSON']); 
    exit; 
}

$inputUrl = (string)($in['input_url'] ?? '');
$mode = (string)($in['mode'] ?? '');
$filename = (string)($in['filename'] ?? 'image.png');

if (!filter_var($inputUrl, FILTER_VALIDATE_URL)) { 
    http_response_code(400); 
    echo json_encode(['success'=>false,'error'=>'Invalid input_url']); 
    exit; 
}
if (!in_array($mode, ['bw','color'], true)) { 
    http_response_code(400); 
    echo json_encode(['success'=>false,'error'=>'Invalid mode - must be "bw" or "color"']); 
    exit; 
}

$filename = basename($filename);

// Rewrite /uploads → signed proxy
$origUrl = $inputUrl;
if (preg_match('~^https?://[^/]+/uploads/([^/]+)$~i', $inputUrl, $m)) {
  $name   = $m[1];
  $secret = getenv('FILE_PROXY_SECRET') ?: '';
  if ($secret === '') { http_response_code(400); echo json_encode(['success'=>false,'code'=>'config_error','error'=>'FILE_PROXY_SECRET not configured']); exit; }
  $sig  = hash_hmac('sha256', $name, $secret);
  $base = trim((string)(getenv('APP_BASE_URL') ?: 'https://vectrahub.online'));
  if (!preg_match('#^https?://#i', $base)) $base = 'https://vectrahub.online';
  $inputUrl = rtrim($base,'/').'/php/api/file_proxy.php?name='.rawurlencode($name).'&sig='.$sig;
}

// Preflight
$probe = Http::headOrGetOk($inputUrl, 8);
if (!$probe['ok']) {
  http_response_code(400);
  echo json_encode([
    'success'=>false,'code'=>'input_unreachable','error'=>'Input URL not reachable',
    'probe'=>$probe,'probe_url'=>$probe['url'] ?? $inputUrl,
    'input_url_before'=>$origUrl,'input_url_after'=>$inputUrl
  ], JSON_UNESCAPED_SLASHES); exit;
}

// Process with runner - never return 5xx to prevent Cloudflare HTML masking
try {
    $client = new RunnerClient();
    $client->checkHealth(); // Fast fail if runner down
    $result = $client->vectorizeStart($inputUrl, $mode, $filename);
    echo json_encode(['success'=>true,'data'=>$result], JSON_UNESCAPED_SLASHES);
} catch (RunnerAuthException $e) {
    error_log('[gpu_vectorize] Runner auth error: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Authentication failed']);
} catch (RunnerBadRequestException $e) {
    error_log('[gpu_vectorize] Runner bad request: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
} catch (RunnerProcessingException|RunnerUnavailableException $e) {
    error_log('[gpu_vectorize] Runner unavailable: ' . $e->getMessage());
    // CRITICAL: Never send 5xx - Cloudflare will mask JSON with HTML
    http_response_code(200);
    echo json_encode(['success'=>false,'code'=>'runner_offline','error'=>'Processing service is unavailable. Please try again shortly.']);
} catch (Throwable $e) {
    error_log('[gpu_vectorize] Unexpected error: ' . $e->getMessage());
    // CRITICAL: Never send 5xx - Cloudflare will mask JSON with HTML
    http_response_code(200);
    echo json_encode(['success'=>false,'code'=>'unexpected','error'=>'Unexpected server error']);
}
exit;