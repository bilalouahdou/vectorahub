<?php

class PythonApiClient {
    private $baseUrl;
    private $client;

    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
        // Initialize a simple HTTP client if needed, or rely on curl directly
        // For this example, we'll use file_get_contents with stream context for POST
    }

    public function vectorizeImage($filename, $imageContent, $contentType) {
        $url = $this->baseUrl . '/vectorize';

        // Create a temporary file for the image content
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempFile, $imageContent);

        // Prepare the cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout

        // Create CURLFile object for file upload
        $cfile = new CURLFile($tempFile, $contentType, $filename);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $cfile]);

        // Set headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: PHP-VectraHub-Client/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Delete the temporary file
        unlink($tempFile);

        if ($response === false) {
            error_log("Python API Client cURL Error: " . $error);
            return ['success' => false, 'error' => 'Network error or API unreachable: ' . $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMessage = $responseData['detail'] ?? 'Unknown API error';
            error_log("Python API Client Error ($httpCode): " . $errorMessage);
            return ['success' => false, 'error' => 'Vectorization service error: ' . $errorMessage];
        }

        return ['success' => true, 'data' => $responseData['data'], 'is_black_image' => $responseData['is_black_image']];
    }
}
?>
