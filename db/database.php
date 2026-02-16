<?php

class Database {
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host=localhost;dbname=deped_inventory;charset=utf8mb4";
            $this->conn = new PDO($dsn, "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            // Improve stability: timeouts and larger packet for images
            $this->conn->exec("SET SESSION wait_timeout=120");
            $this->conn->exec("SET SESSION interactive_timeout=120");
            try { $this->conn->exec("SET SESSION max_allowed_packet=16777216"); } catch (Exception $x) { /* use server default if not allowed */ }
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    /** Reconnect when MySQL has gone away */
    public function reconnect() {
        $this->conn = null;
        $this->connect();
    }

    public function __destruct() {
        $this->conn = null;
    }
}