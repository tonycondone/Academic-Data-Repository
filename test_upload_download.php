<?php
// Test script to diagnose upload/download issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Upload/Download Diagnostic Test</h1>";

// Test 1: Check PHP configuration
echo "<h2>1. PHP Configuration</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Max Upload Size: " . ini_get('upload_max_filesize') . "\n";
echo "Max POST Size: " . ini_get('post_max_size') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";

// Test 2: Check if required files exist
echo "<h2>2. Required Files Check</h2>";
$requiredFiles = [
    'config/config.php',
    'config/database.php',
    'includes/auth.php',
    'includes/functions.php',
    'includes/excel_converter.php'
];

foreach ($requiredFiles as $file) {
    echo $file . ": " . (file_exists($file) ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ MISSING</span>") . "<br>";
}

// Test 3: Check upload directory
echo "<h2>3. Upload Directory Check</h2>";
$uploadDir = 'uploads/';
echo "Upload directory exists: " . (is_dir($uploadDir) ? "<span style='color:green'>✓ YES</span>" : "<span style='color:red'>✗ NO</span>") . "<br>";
echo "Upload directory writable: " . (is_writable($uploadDir) ? "<span style='color:green'>✓ YES</span>" : "<span style='color:red'>✗ NO</span>") . "<br>";

// Test 4: Database connection
echo "<h2>4. Database Connection Test</h2>";

// Try both database configurations
$configs = [
    ['host' => 'localhost', 'dbname' => 'dataset_platform', 'user' => 'root', 'pass' => '1212'],
    ['host' => 'localhost', 'dbname' => 'academic_collaboration', 'user' => 'root', 'pass' => '']
];

$workingConfig = null;
foreach ($configs as $config) {
    try {
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected to database: <span style='color:green'>✓ {$config['dbname']}</span><br>";
        $workingConfig = $config;
        break;
    } catch(PDOException $e) {
        echo "Failed to connect to {$config['dbname']}: <span style='color:red'>" . $e->getMessage() . "</span><br>";
    }
}

// Test 5: Check database tables
if ($workingConfig) {
    echo "<h2>5. Database Tables Check</h2>";
    try {
        $tables = ['users', 'projects', 'files', 'datasets'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "Table '$table': " . ($exists ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ MISSING</span>") . "<br>";
            
            if ($exists && $table == 'files') {
                // Check files table structure
                $stmt = $pdo->query("DESCRIBE files");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "Files table columns: " . implode(', ', $columns) . "<br>";
            }
        }
    } catch(PDOException $e) {
        echo "Error checking tables: " . $e->getMessage();
    }
}

// Test 6: Session check
echo "<h2>6. Session Check</h2>";
session_start();
echo "Session status: " . (session_status() == PHP_SESSION_ACTIVE ? "<span style='color:green'>✓ ACTIVE</span>" : "<span style='color:red'>✗ INACTIVE</span>") . "<br>";
echo "Session ID: " . session_id() . "<br>";

// Test 7: File permissions
echo "<h2>7. Directory Permissions</h2>";
$dirs = ['uploads/', 'versions/', 'config/'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "$dir: $perms " . (is_writable($dir) ? "<span style='color:green'>✓ WRITABLE</span>" : "<span style='color:red'>✗ NOT WRITABLE</span>") . "<br>";
    } else {
        echo "$dir: <span style='color:red'>✗ DOES NOT EXIST</span><br>";
    }
}

// Test 8: Sample file upload test
echo "<h2>8. File Upload Test Form</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Upload Test Results:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $testPath = $uploadDir . 'test_' . time() . '_' . $_FILES['test_file']['name'];
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $testPath)) {
            echo "<span style='color:green'>✓ File uploaded successfully to: $testPath</span><br>";
            echo "File size: " . filesize($testPath) . " bytes<br>";
            // Clean up test file
            unlink($testPath);
            echo "Test file removed.<br>";
        } else {
            echo "<span style='color:red'>✗ Failed to move uploaded file</span><br>";
        }
    } else {
        echo "<span style='color:red'>✗ Upload error code: " . $_FILES['test_file']['error'] . "</span><br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" required>
    <button type="submit">Test Upload</button>
</form>

<h2>9. Recommendations</h2>
<ul>
    <li>Make sure the 'uploads' directory exists and is writable</li>
    <li>Check that your database configuration matches your setup</li>
    <li>Ensure all required PHP extensions are installed (PDO, PDO_MySQL)</li>
    <li>Verify that the database tables are created with the correct schema</li>
    <li>Check PHP error logs for any additional errors</li>
</ul>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
</style>
