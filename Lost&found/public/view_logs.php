<?php
/**
 * Debug Log Viewer
 * 
 * View the debug.log file in your browser
 * Access at: https://yourdomain.com/public/view_logs.php
 */

session_start();
require_once __DIR__ . '/../includes/Logger.php';

// Optional: Add password protection (uncomment to enable)
// if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
//     die('Access denied. Please log in first.');
// }

$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 200; // Number of lines to show
$clear = isset($_GET['clear']) && $_GET['clear'] === '1';

if ($clear) {
    Logger::clear();
    $message = "Log cleared successfully!";
}

$logFile = Logger::getLogFile();
$logContent = Logger::getLastLines($lines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Logs - UB Lost & Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 20px;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        a, button {
            color: #4ec9b0;
            text-decoration: none;
            padding: 8px 16px;
            background: #2d2d30;
            border: 1px solid #4ec9b0;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        a:hover, button:hover {
            background: #4ec9b0;
            color: #1e1e1e;
        }
        .info {
            background: #2d2d30;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #4ec9b0;
        }
        .info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .success {
            background: #2d2d30;
            color: #4ec9b0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #4ec9b0;
        }
        pre {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 70vh;
            overflow-y: auto;
        }
        .empty {
            color: #858585;
            font-style: italic;
            text-align: center;
            padding: 40px;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .controls {
                width: 100%;
            }
            a, button {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Logs</h1>
        
        <?php if (isset($message)): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="header">
            <div class="info">
                <p><strong>Log File:</strong> <?php echo htmlspecialchars($logFile); ?></p>
                <p><strong>Showing:</strong> Last <?php echo $lines; ?> lines</p>
                <p><strong>File Size:</strong> <?php echo file_exists($logFile) ? number_format(filesize($logFile)) . ' bytes' : 'File does not exist'; ?></p>
            </div>
            <div class="controls">
                <a href="?lines=50">Last 50 lines</a>
                <a href="?lines=100">Last 100 lines</a>
                <a href="?lines=200">Last 200 lines</a>
                <a href="?lines=500">Last 500 lines</a>
                <a href="?">Refresh</a>
                <a href="?clear=1" onclick="return confirm('Are you sure you want to clear all logs?')">Clear Logs</a>
            </div>
        </div>
        
        <pre><?php 
            if (empty(trim($logContent))) {
                echo '<div class="empty">Log file is empty. Submit a report with a photo to see logs here.</div>';
            } else {
                echo htmlspecialchars($logContent);
            }
        ?></pre>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

