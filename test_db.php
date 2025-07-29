<?php
// Simple database connection test
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dataset_platform",
        "root",
        "1212",
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    echo "Database connection successful!<br>";
    
    // Test if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "Tables found: " . count($tables) . "<br>";
    foreach($tables as $table) {
        echo "- " . $table['Tables_in_dataset_platform'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>