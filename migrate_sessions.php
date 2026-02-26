<?php
require_once __DIR__ . '/config/config.php';

echo "Migrating database for session handling...\n";

try {
    $pdo = SupabaseService::getConnection();
    // Create generic sessions table for PHP SessionHandler
    $sql = "
    CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        data TEXT,
        last_access TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_sessions_access ON sessions (last_access);
    ";
    
    $pdo->exec($sql);
    echo " sessions table created successfully.\n";
    
} catch (PDOException $e) {
    echo " Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
