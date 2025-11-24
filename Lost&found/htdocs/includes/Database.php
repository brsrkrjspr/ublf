<?php
class Database {
    private $host = '127.0.0.1';
    private $db_name = 'ub_lost_found';
    private $username = 'root';
    private $password = 'besmar012';
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
