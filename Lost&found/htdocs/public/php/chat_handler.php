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
// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:64', 'message' => 'Chatbot webhook config loaded', 'data' => ['webhook_url' => $n8nWebhookUrl, 'api_key_set' => !empty($n8nApiKey), 'student_no' => $studentNo], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

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
        // #region agent log
        $logFile = __DIR__ . '/../../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:125', 'message' => 'Chatbot webhook response received', 'data' => ['http_code' => $httpCode, 'curl_error' => $curlError, 'response_length' => strlen($response ?? ''), 'retry_count' => $retryCount], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        
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
// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$rawResponsePreview = substr($response, 0, 500);
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:191', 'message' => 'Chatbot response parsing', 'data' => ['json_error' => json_last_error(), 'json_error_msg' => json_last_error_msg(), 'has_reply' => isset($n8nResponse['reply']), 'response_keys' => $n8nResponse ? array_keys($n8nResponse) : [], 'raw_response_preview' => $rawResponsePreview, 'full_response_size' => strlen($response)], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'F'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

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

// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$hasWaitMoment = stripos($reply, 'wait') !== false && (stripos($reply, 'moment') !== false || stripos($reply, 'check') !== false || stripos($reply, 'checking') !== false);
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:245', 'message' => 'Final reply extracted', 'data' => ['reply' => substr($reply, 0, 200), 'reply_length' => strlen($reply), 'has_wait_moment' => $hasWaitMoment, 'response_data_keys' => isset($n8nResponse['data']) ? array_keys($n8nResponse['data']) : []], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'F'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

// Detect if response is a "wait a moment" message that indicates incomplete processing
// This happens when n8n workflow responds too early before completing all processing
if ($hasWaitMoment && strlen($reply) < 150) {
    // #region agent log
    $logFile = __DIR__ . '/../../debug.log';
    $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:252', 'message' => 'Detected incomplete wait message', 'data' => ['original_reply' => $reply, 'action' => 'adding_follow_up_note'], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'F'];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    
    // Append a note that the user should send another message or wait
    // This is a workaround - the proper fix is in the n8n workflow to not respond until processing is complete
    $reply .= "\n\n(Processing may take a moment. If you don't see a complete response, please send your message again.)";
}

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

// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'chat_handler.php:258', 'message' => 'Sending response to frontend', 'data' => ['reply_length' => strlen($reply), 'has_data' => isset($n8nResponse['data']), 'full_response' => json_encode(['reply' => $reply, 'data' => $n8nResponse['data'] ?? null])], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'F'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

echo json_encode([
    'reply' => $reply,
    'data' => $n8nResponse['data'] ?? null // Include any additional data from n8n
]);

