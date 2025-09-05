<?php

/**
 * Resend Email Service for VectraHub
 * Handles all transactional emails via Resend API
 */
class EmailService {
    private $apiKey;
    private $fromEmail;
    private $fromName;
    private $apiUrl = 'https://api.resend.com/emails';
    
    public function __construct() {
        $this->apiKey = getenv('RESEND_API_KEY') ?: RESEND_API_KEY;
        $this->fromEmail = 'noreply@vectrahub.online';
        $this->fromName = 'VectraHub';
    }
    
    /**
     * Send email via Resend API
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null, $tags = []) {
        $payload = [
            'from' => $this->fromName . ' <' . $this->fromEmail . '>',
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlContent
        ];
        
        if ($textContent) {
            $payload['text'] = $textContent;
        }
        
        if (!empty($tags)) {
            $payload['tags'] = $tags;
        }
        
        $response = $this->makeApiCall($payload);
        
        // Log email attempt
        error_log("Email sent to {$to}: " . json_encode($response));
        
        return $response;
    }
    
    /**
     * Send welcome email with verification link
     */
    public function sendWelcomeEmail($userEmail, $userName, $verificationToken) {
        $verificationUrl = APP_URL . "/verify-email?token=" . $verificationToken;
        
        $subject = "Welcome to VectraHub - Verify Your Email";
        $htmlContent = $this->loadTemplate('welcome', [
            'user_name' => $userName,
            'verification_url' => $verificationUrl,
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail(
            $userEmail, 
            $subject, 
            $htmlContent,
            null,
            [['name' => 'category', 'value' => 'welcome']]
        );
    }
    
    /**
     * Send subscription expired notification
     */
    public function sendSubscriptionExpiredEmail($userEmail, $userName, $planName) {
        $billingUrl = APP_URL . "/billing";
        
        $subject = "Your VectraHub Subscription Has Expired";
        $htmlContent = $this->loadTemplate('subscription_expired', [
            'user_name' => $userName,
            'plan_name' => $planName,
            'billing_url' => $billingUrl,
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail(
            $userEmail, 
            $subject, 
            $htmlContent,
            null,
            [['name' => 'category', 'value' => 'subscription']]
        );
    }
    
    /**
     * Send incident notification to admins
     */
    public function sendIncidentEmail($errorMessage, $userInfo = [], $requestInfo = []) {
        $adminEmail = 'admin@vectrahub.online';
        
        $subject = "VectraHub Incident Alert - " . date('Y-m-d H:i:s');
        $htmlContent = $this->loadTemplate('incident', [
            'error_message' => $errorMessage,
            'user_info' => $userInfo,
            'request_info' => $requestInfo,
            'timestamp' => date('Y-m-d H:i:s'),
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail(
            $adminEmail, 
            $subject, 
            $htmlContent,
            null,
            [['name' => 'category', 'value' => 'incident']]
        );
    }
    
    /**
     * Load and process email template
     */
    private function loadTemplate($templateName, $variables = []) {
        $templatePath = __DIR__ . "/../templates/email/{$templateName}.html";
        
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: {$templateName}");
        }
        
        $content = file_get_contents($templatePath);
        
        // Replace template variables
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            $content = str_replace('{{' . $key . '}}', htmlspecialchars($value), $content);
        }
        
        return $content;
    }
    
    /**
     * Make API call to Resend
     */
    private function makeApiCall($payload) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: " . $error);
        }
        
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? 'Unknown Resend API error';
            throw new Exception("Resend API error ({$httpCode}): " . $errorMsg);
        }
        
        return $decoded;
    }
    
    /**
     * Queue email for background processing (simple implementation)
     */
    public function queueEmail($type, $params) {
        // For now, send immediately. Can be enhanced with job queue later
        switch ($type) {
            case 'welcome':
                return $this->sendWelcomeEmail($params['email'], $params['name'], $params['token']);
            case 'subscription_expired':
                return $this->sendSubscriptionExpiredEmail($params['email'], $params['name'], $params['plan']);
            case 'incident':
                return $this->sendIncidentEmail($params['error'], $params['user_info'], $params['request_info']);
            default:
                throw new Exception("Unknown email type: {$type}");
        }
    }
}
