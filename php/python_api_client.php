<?php
/**
 * Python API Client for VectorizeAI
 * Handles communication with the Flask API service
 */

class PythonApiClient {
    private $apiUrl;
    
    public function __construct($apiUrl = 'http://localhost:5000') {
        $this->apiUrl = rtrim($apiUrl, '/');
    }
    
    /**
     * Vectorize an image file
     */
    public function vectorizeFile($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }
        
        $endpoint = '/vectorize';
        
        // Create a CURLFile object
        $cfile = new CURLFile($filePath, 'image/jpeg', basename($filePath));
        
        // Set up the POST data
        $postData = [
            'image' => $cfile
        ];
        
        return $this->makeRequest($endpoint, 'POST', $postData, true);
    }
    
    /**
     * Vectorize an image from URL
     */
    public function vectorizeUrl($imageUrl) {
        $endpoint = '/vectorize-url';
        
        // Set up the POST data
        $postData = [
            'url' => $imageUrl
        ];
        
        return $this->makeRequest($endpoint, 'POST', $postData);
    }
    
    /**
     * Download SVG file from the API
     */
    public function downloadSvg($filename, $savePath) {
        // Log attempt
        error_log("PythonApiClient: Attempting to download SVG: $filename to $savePath");
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl . '/download/' . urlencode($filename),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120, // Increased from 60 to 120 seconds
            CURLOPT_CONNECTTIMEOUT => 30, // Increased from 10 to 30 seconds
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            // Fix callback issues
            CURLOPT_NOPROGRESS => true,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            // Set user agent
            CURLOPT_USERAGENT => 'VectorizeAI-PHP-Client/1.0'
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        
        // Log detailed information
        error_log("PythonApiClient: Download Response Code: $httpCode");
        error_log("PythonApiClient: cURL errno: $errno");
        if ($error) {
            error_log("PythonApiClient: cURL Error: $error");
        }
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception("cURL error ($errno): $error");
        }
        
        if ($response === false) {
            throw new Exception("cURL returned false - no response received");
        }
        
        if ($httpCode !== 200) {
            error_log("API download failed with HTTP code $httpCode: $error");
            return false;
        }
        
        // Save the SVG content to the output path
        if (file_put_contents($savePath, $response) === false) {
            error_log("Failed to save SVG to $savePath");
            return false;
        }
        
        return true;
    }
    
    /**
     * Check API health
     */
    public function healthCheck() {
        return $this->makeRequest('/health');
    }
    
    /**
     * Simple test method with minimal file
     */
    public function testUpload() {
        // Create a minimal test image
        $testImage = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($testImage, 255, 255, 255);
        imagefill($testImage, 0, 0, $white);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.png';
        imagepng($testImage, $tempFile);
        imagedestroy($testImage);
        
        try {
            $result = $this->vectorizeFile($tempFile);
            unlink($tempFile); // Cleanup
            return $result;
        } catch (Exception $e) {
            unlink($tempFile); // Cleanup on error
            throw $e;
        }
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = [], $isMultipart = false) {
        // Initialize cURL
        $ch = curl_init($this->apiUrl . $endpoint);
        
        // Set common cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Increased from 30 to 300 seconds (5 minutes)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Increased connection timeout
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            
            if ($isMultipart) {
                // For file uploads
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                // For JSON data
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Close cURL
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            error_log("API request error: $error");
            return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
        }
        
        // Parse JSON response
        $result = json_decode($response, true);
        
        // If not a valid JSON, return the raw response
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("API response is not valid JSON: " . substr($response, 0, 100) . "...");
            return ['success' => false, 'error' => 'Invalid API response', 'http_code' => $httpCode, 'raw_response' => $response];
        }
        
        // Add HTTP code to the result
        $result['http_code'] = $httpCode;
        
        return $result;
    }
}
?>
