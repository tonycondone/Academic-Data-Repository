<?php
// check_schema.php - Check if tables exist
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();

try {
    $pdo = $db->getConnection();
    echo "Connected successfully.<br>";
    
    // Check tables in Postgres
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found: <br>";
    if (empty($tables)) {
        echo "No tables found in 'public' schema.<br>";
        
        // Try to create users table
        echo "Attempting to create 'users' table...<br>";
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ";
        $pdo->exec($sql);
        echo "Table 'users' created (or already exists).<br>";
        
    } else {
        foreach ($tables as $table) {
            echo "- " . htmlspecialchars($table) . "<br>";
            
            // Show columns for users table
            if ($table === 'users') {
                $stmt = $pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?");
                $stmt->execute(['users']);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "  Columns:<br>";
                foreach ($columns as $col) {
                    echo "    - " . htmlspecialchars($col['column_name']) . " (" . htmlspecialchars($col['data_type']) . ")<br>";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
