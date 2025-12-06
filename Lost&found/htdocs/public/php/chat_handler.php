<?php
session_start();
header('Content-Type: application/json');

// Load configuration
require_once __DIR__ . '/../../includes/Config.php';

// Check if user is logged in
if (!isset($_SESSION['student'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please log in to use the chatbot']);
    exit;
}

// Get message from request
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Get student info for context
$studentNo = $_SESSION['student']['StudentNo'] ?? '';
$studentName = $_SESSION['student']['StudentName'] ?? '';
$studentEmail = $_SESSION['student']['Email'] ?? '';

// Get n8n configuration from Config class
$n8nWebhookUrl = Config::get('N8N_WEBHOOK_URL');
$n8nApiKey = Config::get('N8N_API_KEY', '');

// Prepare data to send to n8n
$payload = [
    'message' => trim($message),
    'studentNo' => $studentNo,
    'studentName' => $studentName,
    'studentEmail' => $studentEmail,
    'timestamp' => date('Y-m-d H:i:s'),
    'sessionId' => session_id()
];

// Send to n8n webhook with retry logic
$maxRetries = 2;
$retryCount = 0;
$response = null;
$httpCode = 0;
$curlError = '';

while ($retryCount < $maxRetries) {
    $ch = curl_init($n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: UB-Lost-Found-Chatbot/1.0'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    // Add API key if provided
    if (!empty($n8nApiKey)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            curl_getinfo($ch, CURLINFO_HEADER_OUT) ? [] : ['Content-Type: application/json'],
            ['X-API-Key: ' . $n8nApiKey]
        ));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // If successful, break out of retry loop
    if ($httpCode === 200 && !empty($response)) {
        break;
    }
    
    $retryCount++;
    if ($retryCount < $maxRetries) {
        // Wait 1 second before retry
        sleep(1);
    }
}

// Handle response
if ($curlError) {
    // Log error (in production, use proper logging)
    error_log("Chatbot n8n connection error: " . $curlError);
    
    http_response_code(500);
    echo json_encode([
        'reply' => 'I\'m having trouble connecting right now. Please try again in a moment, or use the dashboard to report items directly.',
        'error' => 'Connection error'
    ]);
    exit;
}

if ($httpCode !== 200) {
    // Log error
    error_log("Chatbot n8n HTTP error: " . $httpCode . " - Response: " . substr($response, 0, 200));
    
    // Provide fallback response
    $fallbackReply = 'I\'m currently unavailable, but you can still:\n\n' .
                     '• Report lost items using the "Report Lost Item" button\n' .
                     '• View your reports in "My Reports"\n' .
                     '• Contact admin directly via "Contact Admin"';
    
    http_response_code(500);
    echo json_encode([
        'reply' => $fallbackReply,
        'error' => 'Service unavailable'
    ]);
    exit;
}

// Parse n8n response
$n8nResponse = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Invalid JSON response
    error_log("Chatbot n8n invalid JSON response: " . substr($response, 0, 200));
    
    echo json_encode([
        'reply' => 'I received an unexpected response. Please try rephrasing your question or contact admin for assistance.',
        'error' => 'Invalid response format'
    ]);
    exit;
}

// Return reply to frontend
echo json_encode([
    'reply' => $n8nResponse['reply'] ?? $n8nResponse['message'] ?? 'I didn\'t understand that. Can you rephrase your question?',
    'data' => $n8nResponse['data'] ?? null // Include any additional data from n8n
]);

