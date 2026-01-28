<?php
/**
 * Database Configuration (Updated for Vercel)
 * Academic Dataset Collaboration Platform
 *
 * Uses getenv() with fallbacks so Vercel env vars take precedence.
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Database connection (Updated for Vercel)
        $this->host     = getenv('DB_HOST') ?: '127.0.0.1';
        $this->db_name  = getenv('DB_NAME') ?: 'dataset_platform';
        $this->username = getenv('DB_USER') ?: 'postgres';
        $this->password = getenv('DB_PASS') ?: 'postgres';
        $this->port     = getenv('DB_PORT') ?: '5432'; // Default Postgres port

        // The '?:' part acts as a local fallback for when you develop on your own computer.
        // When deployed on Vercel, getenv() will fetch the value you set in the Vercel dashboard.
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            // PostgreSQL DSN
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Important for Postgres
                ]
            );
        } catch (PDOException $e) {
            // Re-throw exception so caller can handle it (e.g. fallback to file sessions)
            throw $e;
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>