<?php
// Generate password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password hash for 'admin123': " . $hash . "\n";

// Database connection
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$db_password = '1212';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert or update admin user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $result = $stmt->execute(['Admin User', 'admin@dataset-platform.com', $hash, 'admin']);
    
    if ($result) {
        echo "Admin user created/updated successfully!\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>