<?php
/**
 * Database Connection Test
 * Temporary diagnostic script - DELETE after testing
 */

// Security check
$test_key = isset($_GET['key']) ? $_GET['key'] : '';
if ($test_key !== 'test-2025-12-12') {
    die('Access denied. Use: ?key=test-2025-12-12');
}

echo "<h2>Database Connection Diagnostic</h2>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check environment variables
echo "<h3>1. Environment Variables Check:</h3>";
$env_vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$env_set = true;
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value === false) {
        echo "<p class='error'>❌ $var: NOT SET</p>";
        $env_set = false;
    } else {
        $display = ($var === 'DB_PASS') ? str_repeat('*', strlen($value)) : $value;
        echo "<p class='success'>✅ $var: $display</p>";
    }
}

if (!$env_set) {
    echo "<p class='error'><strong>⚠️ Some environment variables are missing! Check Render Dashboard → Environment Variables</strong></p>";
}

// Test Database connection
echo "<h3>2. Database Connection Test:</h3>";
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = new Database();
    
    // Use reflection to check actual values
    $reflection = new ReflectionClass($db);
    $hostProp = $reflection->getProperty('host');
    $hostProp->setAccessible(true);
    $portProp = $reflection->getProperty('port');
    $portProp->setAccessible(true);
    $dbNameProp = $reflection->getProperty('db_name');
    $dbNameProp->setAccessible(true);
    $userProp = $reflection->getProperty('username');
    $userProp->setAccessible(true);
    $passProp = $reflection->getProperty('password');
    $passProp->setAccessible(true);
    
    echo "<p><strong>Connection Details:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($hostProp->getValue($db)) . "</li>";
    echo "<li>Port: " . htmlspecialchars($portProp->getValue($db)) . "</li>";
    echo "<li>Database: " . htmlspecialchars($dbNameProp->getValue($db)) . "</li>";
    echo "<li>Username: " . htmlspecialchars($userProp->getValue($db)) . "</li>";
    echo "<li>Password: " . (strlen($passProp->getValue($db)) > 0 ? 'SET (' . strlen($passProp->getValue($db)) . ' chars)' : 'EMPTY') . "</li>";
    echo "</ul>";
    
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "<p class='success'><strong>✅ Connection Successful!</strong></p>";
        
        // Test query
        try {
            $stmt = $conn->query("SELECT VERSION() as version, DATABASE() as db");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>MySQL Version: " . htmlspecialchars($result['version']) . "</p>";
            echo "<p>Current Database: " . htmlspecialchars($result['db']) . "</p>";
            
            // Check tables
            $stmt = $conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tables found: " . count($tables) . "</p>";
            if (count($tables) > 0) {
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>" . htmlspecialchars($table) . "</li>";
                }
                echo "</ul>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Query Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error'><strong>❌ Connection Failed!</strong></p>";
        echo "<p>Check Render logs for detailed error messages.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test manual connection
echo "<h3>3. Manual Connection Test:</h3>";
try {
    $host = getenv('DB_HOST') ?: 'mysql-1bd0087e-dullajasperdave-5242.j.aivencloud.com';
    $port = getenv('DB_PORT') ?: 17745;
    $dbname = getenv('DB_NAME') ?: 'ub_lost_found';
    $user = getenv('DB_USER') ?: 'avnadmin';
    $pass = getenv('DB_PASS') ?: 'AVNS_YPXN90v3k7puaeMOcCa';
    
    // Ensure port is integer
    $port = (int)$port;
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_SSL_CA => null,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CIPHER => 'DEFAULT',
    ];
    
    $testConn = new PDO($dsn, $user, $pass, $options);
    echo "<p class='success'>✅ Manual connection successful!</p>";
    $testConn = null;
} catch (PDOException $e) {
    echo "<p class='error'><strong>Manual Connection Failed:</strong></p>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Code: " . $e->getCode() . "</p>";
    echo "<p>DSN: mysql:host=$host;port=$port;dbname=$dbname</p>";
}

echo "<hr>";
echo "<p><strong>⚠️ Delete this file after testing!</strong></p>";
?>

