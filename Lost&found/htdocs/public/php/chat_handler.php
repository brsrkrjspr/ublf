<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

// Load configuration
try {
    require_once __DIR__ . '/../../includes/Config.php';
} catch (Exception $e) {
    error_log("Chatbot Config load error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error. Please contact administrator.']);
    exit;
}

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

// Initialize conversation history in session if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Store user message in conversation history (limit to last 10 messages for context)
$userMessage = trim($message);
$_SESSION['chat_history'][] = [
    'role' => 'user',
    'content' => $userMessage,
    'timestamp' => date('Y-m-d H:i:s')
];

// Keep only last 10 messages (5 user + 5 assistant pairs)
if (count($_SESSION['chat_history']) > 10) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);
}

// Get conversation history (last 10 messages)
$conversationHistory = $_SESSION['chat_history'];

// Get n8n configuration from Config class
$n8nWebhookUrl = Config::get('N8N_WEBHOOK_URL');
$n8nApiKey = Config::get('N8N_API_KEY', '');

// Prepare data to send to n8n
$payload = [
    'message' => $userMessage,
    'studentNo' => $studentNo,
    'studentName' => $studentName,
    'studentEmail' => $studentEmail,
    'timestamp' => date('Y-m-d H:i:s'),
    'sessionId' => session_id(),
    'conversationHistory' => $conversationHistory // Include conversation history
];

// Send to n8n webhook with retry logic
$maxRetries = 2;
$retryCount = 0;
$response = null;
$httpCode = 0;
$curlError = '';

// Check if cURL is available
if (!function_exists('curl_init')) {
    error_log("Chatbot error: cURL extension is not available");
    http_response_code(500);
    echo json_encode([
        'error' => 'Server configuration error. Please contact administrator.',
        'reply' => 'I\'m experiencing technical difficulties. Please try again later or contact support.'
    ]);
    exit;
}

try {
    while ($retryCount < $maxRetries) {
        $ch = curl_init($n8nWebhookUrl);
        if ($ch === false) {
            throw new Exception("Failed to initialize cURL");
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // Build headers array
        $headers = [
            'Content-Type: application/json',
            'User-Agent: UB-Lost-Found-Chatbot/1.0'
        ];
        
        // Add API key if provided
        if (!empty($n8nApiKey)) {
            $headers[] = 'X-API-Key: ' . $n8nApiKey;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
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
} catch (Exception $e) {
    error_log("Chatbot cURL exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Connection error',
        'reply' => 'I\'m having trouble connecting right now. Please try again in a moment.'
    ]);
    exit;
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
    // Invalid JSON response - log the full response for debugging
    error_log("Chatbot n8n invalid JSON response. HTTP Code: " . $httpCode);
    error_log("Chatbot n8n response body: " . substr($response, 0, 500));
    error_log("Chatbot n8n JSON error: " . json_last_error_msg());
    
    // Check if it's an HTML error page or plain text
    if (stripos($response, '<html') !== false || stripos($response, '<!DOCTYPE') !== false) {
        error_log("Chatbot n8n received HTML response instead of JSON - workflow may not be active or webhook URL incorrect");
        echo json_encode([
            'reply' => 'The chatbot service is not properly configured. Please contact the administrator or try again later.',
            'error' => 'Service configuration error'
        ]);
    } else {
        echo json_encode([
            'reply' => 'I received an unexpected response. Please try rephrasing your question or contact admin for assistance.',
            'error' => 'Invalid response format'
        ]);
    }
    exit;
}

// Check if response indicates an error
if (isset($n8nResponse['error'])) {
    error_log("Chatbot n8n returned error: " . json_encode($n8nResponse['error']));
    
    // Provide user-friendly error message
    $errorMessage = $n8nResponse['error']['message'] ?? $n8nResponse['error'] ?? 'An error occurred';
    
    // Check if it's a credential/configuration error
    if (stripos($errorMessage, 'credential') !== false || 
        stripos($errorMessage, 'api key') !== false ||
        stripos($errorMessage, 'authentication') !== false) {
        echo json_encode([
            'reply' => 'The chatbot AI service needs to be configured. Please contact the administrator.',
            'error' => 'Service configuration required'
        ]);
    } else {
        echo json_encode([
            'reply' => $n8nResponse['reply'] ?? 'I encountered an error. Please try again or contact admin for assistance.',
            'error' => $errorMessage
        ]);
    }
    exit;
}

// Return reply to frontend
$reply = $n8nResponse['reply'] ?? $n8nResponse['message'] ?? 'I didn\'t understand that. Can you rephrase your question?';

// Store assistant response in conversation history
$_SESSION['chat_history'][] = [
    'role' => 'assistant',
    'content' => $reply,
    'timestamp' => date('Y-m-d H:i:s')
];

// Keep only last 10 messages
if (count($_SESSION['chat_history']) > 10) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);
}

echo json_encode([
    'reply' => $reply,
    'data' => $n8nResponse['data'] ?? null // Include any additional data from n8n
]);

