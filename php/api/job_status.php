<?php
require_once __DIR__ . '/../config/bootstrap.php';
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../services/RunnerClient.php';

try {
    $id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
    $statusUrl = isset($_GET['status_url']) ? trim((string)$_GET['status_url']) : '';

    // SSRF guard: only allow status_url within RUNNER_BASE_URL host
    if ($statusUrl) {
        $base = rtrim((string)getenv('RUNNER_BASE_URL'), '/');
        $bh = parse_url($base, PHP_URL_HOST);
        $sh = parse_url($statusUrl, PHP_URL_HOST);
        if (!$bh || !$sh || strcasecmp($bh, $sh) !== 0) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Invalid status_url host'], JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    $runner = new RunnerClient();
    $r = $runner->status($id ?: null, $statusUrl ?: null);

    if (!$r['ok']) {
        http_response_code($r['status'] ?: 502);
        echo json_encode([
            'success'=>false,
            'code'=>$r['status'],
            'error'=> $r['error'] ?: 'Runner status error',
            'debug_url'=> $r['url'] ?? null,
            'raw'=> $r['raw'],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
    $j = $r['json'] ?? [];
    $data = [
        'job_id'      => $id ?: null,
        'status'      => ($j['status'] ?? $j['state'] ?? 'unknown'),
        'output_url'  => ($j['output_url'] ?? ($j['output']['url'] ?? null)),
        'local_path'  => ($j['output']['local_path'] ?? ($j['local_path'] ?? null)),
    ];
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[job_status] Unexpected error: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode(['success' => false, 'code' => 'unexpected', 'error' => 'Server error']);
}
