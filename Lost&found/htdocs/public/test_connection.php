<?php
/**
 * Database Connection Test Script
 * This will help diagnose connection issues
 */

// Security - remove after testing
$test_key = isset($_GET['key']) ? $_GET['key'] : '';
if ($test_key !== 'test-2025-12-12') {
    die('Access denied. Use: ?key=test-2025-12-12');
}

echo "<h2>Database Connection Diagnostic</h2>";

// Check environment variables
echo "<h3>Environment Variables:</h3>";
echo "<pre>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET (using default)') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET (using default)') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET (using default)') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET (using default)') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'SET (hidden)' : 'NOT SET (using default)') . "\n";
echo "</pre>";

require_once __DIR__ . '/../includes/Database.php';

$db = new Database();

// Get the actual values being used
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

echo "<h3>Actual Connection Values:</h3>";
echo "<pre>";
echo "Host: " . $hostProp->getValue($db) . "\n";
echo "Port: " . $portProp->getValue($db) . "\n";
echo "Database: " . $dbNameProp->getValue($db) . "\n";
echo "Username: " . $userProp->getValue($db) . "\n";
echo "Password: " . (strlen($passProp->getValue($db)) > 0 ? 'SET (' . strlen($passProp->getValue($db)) . ' chars)' : 'EMPTY') . "\n";
echo "</pre>";

// Try to connect
echo "<h3>Connection Test:</h3>";
$conn = $db->getConnection();

if ($conn) {
    echo "<p style='color: green;'><strong>✅ Connection successful!</strong></p>";
    
    // Test query
    try {
        $stmt = $conn->query("SELECT VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>MySQL Version: " . htmlspecialchars($result['version']) . "</p>";
        
        // Check if database exists
        $stmt = $conn->query("SELECT DATABASE() as current_db");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Current Database: " . htmlspecialchars($result['current_db']) . "</p>";
        
        // List tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables in database: " . count($tables) . "</p>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>Query error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ Connection failed!</strong></p>";
    
    // Try to get more details
    echo "<h4>Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li>Check if environment variables are set in Render Dashboard</li>";
    echo "<li>Verify database name 'ub_lost_found' exists in Aiven</li>";
    echo "<li>Check Aiven service status</li>";
    echo "<li>Verify credentials are correct</li>";
    echo "<li>Check Render logs for detailed error messages</li>";
    echo "</ul>";
    
    // Try manual connection with error display
    echo "<h4>Detailed Error Test:</h4>";
    try {
        $host = $hostProp->getValue($db);
        $port = $portProp->getValue($db);
        $dbname = $dbNameProp->getValue($db);
        $user = $userProp->getValue($db);
        $pass = $passProp->getValue($db);
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_CA => null,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        $testConn = new PDO($dsn, $user, $pass, $options);
        echo "<p style='color: green;'>✅ Manual connection successful!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'><strong>Error Details:</strong></p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>⚠️ Delete this file after testing!</strong></p>";
?>

