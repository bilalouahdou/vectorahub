<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config.php'; // should start session + give $pdo

try {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['success'=>false,'error'=>'Auth required']); 
    exit; 
  }
  
  $raw = file_get_contents('php://input'); 
  $data = json_decode($raw,true);
  $input = trim((string)($data['input_url'] ?? ''));
  $output= trim((string)($data['output_url'] ?? ''));
  $mode  = in_array(($data['mode'] ?? ''),['bw','color'],true) ? $data['mode'] : 'color';
  
  if ($output===''){
    http_response_code(400); 
    echo json_encode(['success'=>false,'error'=>'output_url required']); 
    exit; 
  }
  
  $cost = ($mode==='bw') ? 0.5 : 1.0;  // adjust if your pricing differs
  $uid  = (int)$_SESSION['user_id'];

  $pdo = getDBConnection();
  $pdo->beginTransaction();
  
  // Check if user has enough coins
  $currentCoins = getUserCoinsRemaining($uid);
  if ($currentCoins < $cost) {
    $pdo->rollBack(); 
    http_response_code(402); 
    echo json_encode(['success'=>false,'error'=>'Insufficient credits']); 
    exit; 
  }
  
  // Record coin usage
  $stmt = $pdo->prepare("INSERT INTO coin_usage (user_id, coins_used, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
  $stmt->execute([$uid, $cost]);
  
  // Insert into image_jobs for history
  $stmt = $pdo->prepare("INSERT INTO image_jobs (user_id, original_image_path, output_svg_path, status, created_at) VALUES (?, ?, ?, 'done', CURRENT_TIMESTAMP)");
  $stmt->execute([$uid, $input ?: null, $output]);
  $jobId = (int)$pdo->lastInsertId();
  
  // Get updated balance
  $coinsRemaining = getUserCoinsRemaining($uid);
  
  $pdo->commit();
  echo json_encode([
    'success'=>true,
    'history_id'=>$jobId,
    'coins_remaining'=>$coinsRemaining
  ], JSON_UNESCAPED_SLASHES);
  
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log('[record_result] Error: ' . $e->getMessage());
  http_response_code(200);
  echo json_encode(['success'=>false,'code'=>'unexpected','error'=>'Server error'], JSON_UNESCAPED_SLASHES);
}
