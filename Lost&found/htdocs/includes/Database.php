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
        // Use $_ENV as fallback if getenv() doesn't work
        $this->host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'mysql-1bd0087e-dullajasperdave-5242.j.aivencloud.com');
        $this->db_name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'ub_lost_found');
        $this->username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'avnadmin');
        $this->password = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'AVNS_YPXN90v3k7puaeMOcCa');
        // Ensure port is integer
        $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 17745);
        $this->port = (int)$port;
    }

    public function getConnection() {
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
        } catch(PDOException $exception) {
            // Log detailed error for debugging
            $error_msg = "Database connection error: " . $exception->getMessage() . 
                        " | Code: " . $exception->getCode() . 
                        " | SQL State: " . $exception->getCode() .
                        " | Host: " . $this->host . 
                        " | Port: " . $this->port . 
                        " | Database: " . $this->db_name .
                        " | Username: " . $this->username;
            error_log($error_msg);
            
            // Also log to a file for easier debugging on Render
            $logFile = __DIR__ . '/../db_errors.log';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $error_msg . "\n", FILE_APPEND);
            
            return null;
        }
        return $this->conn;
    }
}
