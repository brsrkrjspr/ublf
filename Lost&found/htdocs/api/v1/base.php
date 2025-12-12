<?php
header('Content-Type: application/json');

// Load configuration
require_once __DIR__ . '/../../includes/Config.php';

// CORS headers (if needed for n8n)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API Key Authentication
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';

// Get API key from Config class
$validApiKey = Config::get('API_KEY');
// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'base.php:19', 'message' => 'API authentication check', 'data' => ['api_key_provided' => !empty($apiKey), 'api_key_length' => strlen($apiKey), 'valid_key_set' => !empty($validApiKey), 'keys_match' => $apiKey === $validApiKey], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

// Validate API key
if (empty($apiKey) || $apiKey !== $validApiKey) {
    // #region agent log
    $logFile = __DIR__ . '/../../debug.log';
    $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'base.php:26', 'message' => 'API authentication failed', 'data' => ['reason' => empty($apiKey) ? 'missing' : 'mismatch'], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D'];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    // #endregion
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Invalid or missing API key'
    ]);
    exit;
}
// #region agent log
$logFile = __DIR__ . '/../../debug.log';
$logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'base.php:33', 'message' => 'API authentication success', 'data' => ['authenticated' => true], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'D'];
@file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
// #endregion

// Include database and classes
require_once __DIR__ . '/../../includes/Database.php';

// Helper function for JSON responses
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper function for error responses
function jsonError($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'error' => $message
    ], $statusCode);
}

