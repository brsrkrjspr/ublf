<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'gcrajoqq_ublf';
    private $username = 'gcrajoqq_ublf';
    private $password = 'ublf12345';
    private $conn;

    public function getConnection() {
        // #region agent log
        $logFile = __DIR__ . '/../debug.log';
        $logData = ['id' => 'log_' . time() . '_' . uniqid(), 'timestamp' => round(microtime(true) * 1000), 'location' => 'Database.php:9', 'message' => 'Database connection attempt', 'data' => ['host' => $this->host, 'db_name' => $this->db_name, 'username' => $this->username], 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A'];
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
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
            return null;
        }
        return $this->conn;
    }
}
