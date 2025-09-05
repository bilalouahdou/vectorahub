<?php
declare(strict_types=1);

require_once __DIR__ . '/RunnerExceptions.php';

final class RunnerClient {
    private string $base;
    private string $token;

    public function __construct() {
        $this->base  = rtrim(getenv('RUNNER_BASE_URL') ?: '', '/');
        $this->token = getenv('RUNNER_SHARED_TOKEN') ?: (getenv('RUNNER_TOKEN') ?: '');
        if ($this->base === '')  throw new RunnerUnavailableException('runner-base-missing');
        if ($this->token === '') throw new RunnerUnavailableException('runner-token-missing');
    }

    public function checkHealth(): void {
        $this->curl('GET', '/health', null, 10); // throws if not healthy
    }

    public function vectorizeStart(string $url, string $mode, string $filename): array {
        return $this->curl('POST', '/run', [
            'input_url' => $url,
            'mode'      => $mode,      // 'color' | 'bw'
            'filename'  => $filename
        ], 15);
    }

    private function curl(string $method, string $path, ?array $payload, int $timeout): array {
        $ch = curl_init($this->base . $path);
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $this->token];
        
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        }
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($err) throw new RunnerUnavailableException('curl: ' . $err);
        
        $json = json_decode((string)$raw, true);
        if ($code === 200 && is_array($json)) return $json;
        if ($code === 401) throw new RunnerAuthException('unauthorized');
        if ($code === 400) throw new RunnerBadRequestException($json['error'] ?? 'bad request');
        if ($code >= 500) throw new RunnerProcessingException($json['error'] ?? 'runner 5xx');
        
        throw new RunnerProcessingException('unexpected status ' . $code);
    }

    public function status(?string $jobId, ?string $statusUrl = null): array {
        // For backwards compatibility, we'll wrap the old status interface
        // The new health-checked version should use the curl method directly
        try {
            if ($statusUrl && preg_match('#^https?://#i', $statusUrl)) {
                // Direct status URL call - would need modification to curl method
                // For now, fall back to job ID
            }
            $jobId = trim((string)$jobId, " \t\n\r\0\x0B\"'");
            return $this->curl('GET', "/status/{$jobId}", null, 30);
        } catch (RunnerUnavailableException | RunnerProcessingException $e) {
            // Return old format for backwards compatibility
            return ['ok' => false, 'status' => 0, 'json' => null, 'raw' => null, 'error' => $e->getMessage()];
        }
    }
}