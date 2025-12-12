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
                        " | Host: " . $this->host . 
                        " | Port: " . $this->port . 
                        " | Database: " . $this->db_name;
            error_log($error_msg);
            return null;
        }
        return $this->conn;
    }
}
