<?php
/**
 * Test endpoint for debugging chatbot issues
 * Access via: https://ublf.x10.mx/public/php/test_chatbot.php
 * 
 * This will show you exactly what n8n is returning
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

// Load configuration
require_once __DIR__ . '/../../includes/Config.php';

$n8nWebhookUrl = Config::get('N8N_WEBHOOK_URL');
$n8nApiKey = Config::get('N8N_API_KEY', '');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chatbot Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .error { border-left-color: #dc3545; background: #fff5f5; }
        .success { border-left-color: #28a745; background: #f0fff4; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .test-form { margin: 20px 0; }
        input, textarea { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .info { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Chatbot Debug Test</h1>
        
        <div class="section">
            <h2>Configuration</h2>
            <p><strong>n8n Webhook URL:</strong> <?php echo htmlspecialchars($n8nWebhookUrl); ?></p>
            <p><strong>n8n API Key:</strong> <?php echo !empty($n8nApiKey) ? '✅ Set' : '❌ Not set'; ?></p>
            <p><strong>Session Status:</strong> <?php echo isset($_SESSION['student']) ? '✅ Logged in as ' . htmlspecialchars($_SESSION['student']['StudentNo']) : '❌ Not logged in'; ?></p>
        </div>

        <?php if (isset($_POST['test_message'])): ?>
            <?php
            $testMessage = $_POST['test_message'] ?? 'hello';
            $studentNo = $_SESSION['student']['StudentNo'] ?? 'TEST001';
            $studentName = $_SESSION['student']['StudentName'] ?? 'Test User';
            $studentEmail = $_SESSION['student']['Email'] ?? 'test@ub.edu.ph';
            
            $payload = [
                'message' => trim($testMessage),
                'studentNo' => $studentNo,
                'studentName' => $studentName,
                'studentEmail' => $studentEmail,
                'timestamp' => date('Y-m-d H:i:s'),
                'sessionId' => session_id()
            ];
            
            // Make request to n8n
            $ch = curl_init($n8nWebhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            $headers = [
                'Content-Type: application/json',
                'User-Agent: UB-Lost-Found-Chatbot/1.0'
            ];
            
            if (!empty($n8nApiKey)) {
                $headers[] = 'X-API-Key: ' . $n8nApiKey;
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);
            ?>
            
            <div class="section <?php echo $httpCode === 200 ? 'success' : 'error'; ?>">
                <h2>Test Results</h2>
                
                <h3>Request Details</h3>
                <pre><?php echo htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)); ?></pre>
                
                <h3>HTTP Response</h3>
                <p><strong>Status Code:</strong> <?php echo $httpCode; ?></p>
                <p><strong>cURL Error:</strong> <?php echo $curlError ?: 'None'; ?></p>
                <p><strong>Response Time:</strong> <?php echo round($curlInfo['total_time'], 2); ?>s</p>
                
                <h3>Raw Response from n8n</h3>
                <pre><?php echo htmlspecialchars($response ?: '(empty response)'); ?></pre>
                
                <h3>JSON Parsing</h3>
                <?php
                $jsonData = json_decode($response, true);
                $jsonError = json_last_error();
                ?>
                <p><strong>Valid JSON:</strong> <?php echo $jsonError === JSON_ERROR_NONE ? '✅ Yes' : '❌ No'; ?></p>
                <?php if ($jsonError !== JSON_ERROR_NONE): ?>
                    <p><strong>JSON Error:</strong> <?php echo json_last_error_msg(); ?></p>
                <?php endif; ?>
                
                <?php if ($jsonError === JSON_ERROR_NONE && $jsonData): ?>
                    <h3>Parsed JSON Data</h3>
                    <pre><?php echo htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    
                    <h3>Expected Fields</h3>
                    <ul>
                        <li><strong>reply:</strong> <?php echo isset($jsonData['reply']) ? '✅ "' . htmlspecialchars($jsonData['reply']) . '"' : '❌ Missing'; ?></li>
                        <li><strong>message:</strong> <?php echo isset($jsonData['message']) ? '✅ "' . htmlspecialchars($jsonData['message']) . '"' : '⚠️ Missing (optional)'; ?></li>
                        <li><strong>error:</strong> <?php echo isset($jsonData['error']) ? '⚠️ "' . htmlspecialchars($jsonData['error']) . '"' : '✅ None'; ?></li>
                    </ul>
                <?php endif; ?>
                
                <h3>Diagnosis</h3>
                <?php if ($curlError): ?>
                    <p class="error">❌ <strong>Connection Error:</strong> Cannot connect to n8n webhook. Check URL and network.</p>
                <?php elseif ($httpCode === 404): ?>
                    <p class="error">❌ <strong>404 Not Found:</strong> Webhook URL is incorrect or workflow is not activated.</p>
                <?php elseif ($httpCode === 500): ?>
                    <p class="error">❌ <strong>500 Server Error:</strong> n8n workflow has an error. Check n8n execution logs.</p>
                <?php elseif ($httpCode !== 200): ?>
                    <p class="error">❌ <strong>HTTP <?php echo $httpCode; ?>:</strong> Unexpected status code from n8n.</p>
                <?php elseif ($jsonError !== JSON_ERROR_NONE): ?>
                    <p class="error">❌ <strong>Invalid JSON:</strong> n8n returned non-JSON response. Check workflow configuration.</p>
                    <?php if (stripos($response, '<html') !== false): ?>
                        <p>⚠️ Response appears to be HTML - workflow may not be active or webhook URL is wrong.</p>
                    <?php endif; ?>
                <?php elseif (!isset($jsonData['reply']) && !isset($jsonData['message'])): ?>
                    <p class="error">❌ <strong>Missing Reply Field:</strong> Response is valid JSON but missing 'reply' or 'message' field.</p>
                <?php else: ?>
                    <p class="success">✅ <strong>Success!</strong> n8n is returning valid JSON with a reply field.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Test Chatbot</h2>
            <form method="POST" class="test-form">
                <label>Test Message:</label>
                <input type="text" name="test_message" value="hello" placeholder="Type a test message">
                <button type="submit">Test n8n Webhook</button>
            </form>
            <p class="info">This will send a test message to your n8n webhook and show you exactly what it returns.</p>
        </div>
        
        <div class="section">
            <h2>Common Issues & Solutions</h2>
            <ul>
                <li><strong>404 Error:</strong> Workflow not activated or wrong webhook URL</li>
                <li><strong>500 Error:</strong> Check n8n execution logs - likely OpenAI credential issue</li>
                <li><strong>Invalid JSON:</strong> Workflow error handler not returning proper JSON format</li>
                <li><strong>HTML Response:</strong> Webhook URL points to wrong endpoint or workflow inactive</li>
                <li><strong>Missing 'reply' field:</strong> Check "Format Response" node in n8n workflow</li>
            </ul>
        </div>
    </div>
</body>
</html>

