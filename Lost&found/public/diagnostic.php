<?php
/**
 * System Diagnostic Script
 * 
 * Tests critical system components:
 * - Database connectivity
 * - Configuration loading
 * - Chatbot webhook connectivity
 * - API authentication
 * - Session management
 */

session_start();
header('Content-Type: application/json');

$logFile = __DIR__ . '/../../.cursor/debug.log';
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Helper function to log diagnostic results
function logDiagnostic($logFile, $location, $message, $data, $hypothesisId) {
    $logData = [
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => round(microtime(true) * 1000),
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'sessionId' => 'debug-session',
        'runId' => 'diagnostic',
        'hypothesisId' => $hypothesisId
    ];
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
}

// Test 1: Database Connection (Hypothesis A)
logDiagnostic($logFile, 'diagnostic.php:1', 'Database connection test started', [], 'A');
try {
    require_once __DIR__ . '/../includes/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn === null) {
        $results['tests']['database'] = ['status' => 'FAIL', 'message' => 'Database connection returned null'];
        logDiagnostic($logFile, 'diagnostic.php:2', 'Database connection test failed', ['reason' => 'null_connection'], 'A');
    } else {
        // Test query
        $stmt = $conn->query("SELECT 1");
        if ($stmt) {
            $results['tests']['database'] = ['status' => 'PASS', 'message' => 'Database connection successful'];
            logDiagnostic($logFile, 'diagnostic.php:3', 'Database connection test passed', ['connected' => true], 'A');
        } else {
            $results['tests']['database'] = ['status' => 'FAIL', 'message' => 'Database connection established but query failed'];
            logDiagnostic($logFile, 'diagnostic.php:4', 'Database query test failed', ['reason' => 'query_failed'], 'A');
        }
    }
} catch (Exception $e) {
    $results['tests']['database'] = ['status' => 'FAIL', 'message' => 'Database exception: ' . $e->getMessage()];
    logDiagnostic($logFile, 'diagnostic.php:5', 'Database exception', ['error' => $e->getMessage()], 'A');
}

// Test 2: Configuration Loading (Hypothesis B)
logDiagnostic($logFile, 'diagnostic.php:6', 'Configuration loading test started', [], 'B');
try {
    require_once __DIR__ . '/../includes/Config.php';
    $n8nUrl = Config::get('N8N_WEBHOOK_URL');
    $apiKey = Config::get('API_KEY');
    $env = Config::get('ENVIRONMENT');
    
    if (empty($n8nUrl)) {
        $results['tests']['config'] = ['status' => 'FAIL', 'message' => 'N8N_WEBHOOK_URL is empty'];
        logDiagnostic($logFile, 'diagnostic.php:7', 'Config test failed - empty webhook URL', ['n8n_url' => $n8nUrl], 'B');
    } else {
        $results['tests']['config'] = [
            'status' => 'PASS',
            'message' => 'Configuration loaded successfully',
            'data' => [
                'n8n_webhook_url' => $n8nUrl,
                'api_key_set' => !empty($apiKey),
                'environment' => $env
            ]
        ];
        logDiagnostic($logFile, 'diagnostic.php:8', 'Config test passed', ['n8n_url' => $n8nUrl, 'api_key_set' => !empty($apiKey)], 'B');
    }
} catch (Exception $e) {
    $results['tests']['config'] = ['status' => 'FAIL', 'message' => 'Config exception: ' . $e->getMessage()];
    logDiagnostic($logFile, 'diagnostic.php:9', 'Config exception', ['error' => $e->getMessage()], 'B');
}

// Test 3: Chatbot Webhook Connectivity (Hypothesis C)
logDiagnostic($logFile, 'diagnostic.php:10', 'Chatbot webhook test started', [], 'C');
try {
    require_once __DIR__ . '/../includes/Config.php';
    $n8nWebhookUrl = Config::get('N8N_WEBHOOK_URL');
    
    if (empty($n8nWebhookUrl)) {
        $results['tests']['chatbot_webhook'] = ['status' => 'SKIP', 'message' => 'Webhook URL not configured'];
        logDiagnostic($logFile, 'diagnostic.php:11', 'Webhook test skipped - no URL', [], 'C');
    } else {
        $testPayload = [
            'message' => 'test',
            'studentNo' => 'DIAGNOSTIC',
            'studentName' => 'Diagnostic Test',
            'studentEmail' => 'diagnostic@ub.edu.ph',
            'timestamp' => date('Y-m-d H:i:s'),
            'sessionId' => 'diagnostic-session',
            'conversationHistory' => []
        ];
        
        $ch = curl_init($n8nWebhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        logDiagnostic($logFile, 'diagnostic.php:12', 'Webhook response received', [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response_length' => strlen($response ?? '')
        ], 'C');
        
        if ($curlError) {
            $results['tests']['chatbot_webhook'] = ['status' => 'FAIL', 'message' => 'cURL error: ' . $curlError];
        } elseif ($httpCode !== 200) {
            $results['tests']['chatbot_webhook'] = ['status' => 'FAIL', 'message' => 'HTTP ' . $httpCode];
        } else {
            $jsonResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['reply'])) {
                $results['tests']['chatbot_webhook'] = ['status' => 'PASS', 'message' => 'Webhook responded successfully'];
            } else {
                $results['tests']['chatbot_webhook'] = ['status' => 'FAIL', 'message' => 'Invalid JSON response or missing reply field'];
            }
        }
    }
} catch (Exception $e) {
    $results['tests']['chatbot_webhook'] = ['status' => 'FAIL', 'message' => 'Exception: ' . $e->getMessage()];
    logDiagnostic($logFile, 'diagnostic.php:13', 'Webhook exception', ['error' => $e->getMessage()], 'C');
}

// Test 4: API Authentication (Hypothesis D)
logDiagnostic($logFile, 'diagnostic.php:14', 'API authentication test started', [], 'D');
try {
    require_once __DIR__ . '/../includes/Config.php';
    $validApiKey = Config::get('API_KEY');
    
    if (empty($validApiKey)) {
        $results['tests']['api_auth'] = ['status' => 'FAIL', 'message' => 'API_KEY not configured'];
        logDiagnostic($logFile, 'diagnostic.php:15', 'API auth test failed - no key', [], 'D');
    } else {
        // Test with valid key
        $_SERVER['HTTP_X_API_KEY'] = $validApiKey;
        require_once __DIR__ . '/../api/v1/base.php';
        // If we get here, authentication passed (base.php would exit on failure)
        $results['tests']['api_auth'] = ['status' => 'PASS', 'message' => 'API authentication working'];
        logDiagnostic($logFile, 'diagnostic.php:16', 'API auth test passed', ['authenticated' => true], 'D');
    }
} catch (Exception $e) {
    $results['tests']['api_auth'] = ['status' => 'FAIL', 'message' => 'Exception: ' . $e->getMessage()];
    logDiagnostic($logFile, 'diagnostic.php:17', 'API auth exception', ['error' => $e->getMessage()], 'D');
}

// Test 5: Session Management (Hypothesis E)
logDiagnostic($logFile, 'diagnostic.php:18', 'Session management test started', [], 'E');
try {
    $testKey = 'diagnostic_test';
    $testValue = 'test_value_' . time();
    $_SESSION[$testKey] = $testValue;
    session_write_close();
    session_start();
    
    if (isset($_SESSION[$testKey]) && $_SESSION[$testKey] === $testValue) {
        $results['tests']['session'] = ['status' => 'PASS', 'message' => 'Session persistence working'];
        logDiagnostic($logFile, 'diagnostic.php:19', 'Session test passed', ['session_id' => session_id()], 'E');
    } else {
        $results['tests']['session'] = ['status' => 'FAIL', 'message' => 'Session data not persisting'];
        logDiagnostic($logFile, 'diagnostic.php:20', 'Session test failed', ['reason' => 'data_not_persisted'], 'E');
    }
    unset($_SESSION[$testKey]);
} catch (Exception $e) {
    $results['tests']['session'] = ['status' => 'FAIL', 'message' => 'Exception: ' . $e->getMessage()];
    logDiagnostic($logFile, 'diagnostic.php:21', 'Session exception', ['error' => $e->getMessage()], 'E');
}

// Calculate summary
$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($results['tests'] as $test) {
    if ($test['status'] === 'PASS') $passed++;
    elseif ($test['status'] === 'FAIL') $failed++;
    else $skipped++;
}

$results['summary'] = [
    'total' => count($results['tests']),
    'passed' => $passed,
    'failed' => $failed,
    'skipped' => $skipped
];

logDiagnostic($logFile, 'diagnostic.php:22', 'Diagnostic complete', $results['summary'], 'SUMMARY');

echo json_encode($results, JSON_PRETTY_PRINT);

