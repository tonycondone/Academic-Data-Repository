<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'dataset_platform';
$username = 'root';
$password = '1212';

// Debug function
function debugLog($message) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . "\n", 3, 'download_debug.log');
}

try {
    debugLog("Starting download process");
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        debugLog("User not logged in");
        header('Location: login.php');
        exit;
    }

    $datasetId = (int)($_GET['id'] ?? 0);
    debugLog("Dataset ID: " . $datasetId);

    if (!$datasetId) {
        debugLog("Invalid dataset ID");
        header('Location: index.php');
        exit;
    }

    // Database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debugLog("Database connected successfully");

    // Start transaction
    $pdo->beginTransaction();
    
    // Get dataset details
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ? AND is_active = 1");
    $stmt->execute([$datasetId]);
    $dataset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dataset) {
        debugLog("Dataset not found or inactive");
        throw new Exception("Dataset not found");
    }

    debugLog("Dataset found: " . $dataset['title']);

    // Check if file exists
    if (!file_exists($dataset['file_path'])) {
        debugLog("File not found: " . $dataset['file_path']);
        throw new Exception("File not found on server");
    }

    // Update download count
    $stmt = $pdo->prepare("UPDATE datasets SET download_count = download_count + 1 WHERE id = ?");
    $updateResult = $stmt->execute([$datasetId]);
    
    if (!$updateResult) {
        debugLog("Failed to update download count");
        throw new Exception("Failed to update download count");
    }
    
    debugLog("Download count updated successfully");

    // Log download
    $stmt = $pdo->prepare("INSERT INTO downloads (dataset_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $datasetId,
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $pdo->commit();
    debugLog("Transaction committed successfully");

    // Continue with file download...
    // [Rest of the download logic from original download.php]

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    debugLog("Error: " . $e->getMessage());
    $_SESSION['error'] = 'Download failed: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>
