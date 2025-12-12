<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Read from environment variables (set in Render) or use Aiven defaults
        $this->host = getenv('DB_HOST') ?: 'mysql-1bd0087e-dullajasperdave-5242.j.aivencloud.com';
        $this->db_name = getenv('DB_NAME') ?: 'ub_lost_found';
        $this->username = getenv('DB_USER') ?: 'avnadmin';
        $this->password = getenv('DB_PASS') ?: 'AVNS_YPXN90v3k7puaeMOcCa';
        $this->port = getenv('DB_PORT') ?: 17745;
    }

    public function getConnection() {
        // #region agent log
        $logFile = __DIR__ . '/../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Database.php:9', 'message' => 'Database connection attempt', 'data' => ['host' => $this->host, 'db_name' => $this->db_name, 'username' => $this->username], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        $this->conn = null;
        try {
            // Aiven requires SSL connection
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_SSL_CA => null,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_SSL_CIPHER => 'DEFAULT',
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
            // #region agent log
            $logFile = __DIR__ . '/../debug.log';
            $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Database.php:15', 'message' => 'Database connection success', 'data' => ['connected' => true], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A'];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
        } catch(PDOException $exception) {
            // #region agent log
            $logFile = __DIR__ . '/../debug.log';
            $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Database.php:18', 'message' => 'Database connection failed', 'data' => ['error' => $exception->getMessage(), 'code' => $exception->getCode()], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A'];
            @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            // Return null instead of dying - allows pages to load without database
            // Uncomment the line below to see connection errors during development
            // error_log("Database connection error: " . $exception->getMessage());
            // Log detailed error for debugging
            $error_msg = "Database connection error: " . $exception->getMessage() . 
                        " | Code: " . $exception->getCode() . 
                        " | Host: " . $this->host . 
                        " | Port: " . $this->port . 
                        " | Database: " . $this->db_name;
            error_log($error_msg);
            return null;
        }
        return $this->conn;
    }
}
