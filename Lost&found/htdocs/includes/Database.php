<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'gcrajoqq_ublf';
    private $username = 'gcrajoqq_ublf';
    private $password = 'ublf12345';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // Return null instead of dying - allows pages to load without database
            // Uncomment the line below to see connection errors during development
            // error_log("Database connection error: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}
